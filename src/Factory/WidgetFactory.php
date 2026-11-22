<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\WidgetCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetSectionDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetSection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Builds the widgets that the current user can see in a dashboard.
 *
 * @internal
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class WidgetFactory
{
    /**
     * The name of the method of the dashboard controller that serves
     * the contents of a single widget (for lazy and refreshing widgets).
     */
    public const RENDER_WIDGET_METHOD = 'renderWidget';

    /** @var \WeakMap<Request, WidgetCollection> */
    private \WeakMap $widgetsOfRequest;

    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly ControllerFactory $controllerFactory,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
        $this->widgetsOfRequest = new \WeakMap();
    }

    /**
     * Returns the widgets of the dashboard of the current request. They are
     * built only once per request, no matter how many times this is called.
     */
    public function getWidgets(): WidgetCollection
    {
        $adminContext = $this->adminContextProvider->getContext();
        if (null === $adminContext) {
            return new WidgetCollection();
        }

        $request = $adminContext->getRequest();
        if (isset($this->widgetsOfRequest[$request])) {
            return $this->widgetsOfRequest[$request];
        }

        $dashboardController = $this->controllerFactory->getDashboardControllerInstance($adminContext->getDashboardControllerFqcn(), $request);

        return $this->widgetsOfRequest[$request] = null === $dashboardController ? new WidgetCollection() : $this->create($dashboardController);
    }

    public function create(DashboardControllerInterface $dashboardController): WidgetCollection
    {
        // dashboards that implement the interface without extending the abstract controller may not define this method
        if (!method_exists($dashboardController, 'configureWidgets')) {
            return new WidgetCollection();
        }

        $currentSection = new WidgetSectionDto();
        $isCurrentSectionGranted = true;
        $sections = [$currentSection];
        $toolbarWidgets = [];
        $deniedNames = [];
        $usedWidgetNames = [];
        $usedSectionNames = [];
        $slugger = new AsciiSlugger();
        $position = 0;

        foreach ($dashboardController->configureWidgets() as $widgetOrSection) {
            if ($widgetOrSection instanceof WidgetSection) {
                // DTOs are cloned because the same widget and section objects can be returned more than once (e.g. when the application stores them)
                $currentSection = clone $widgetOrSection->getAsDto();
                $this->configureSectionName($currentSection, $usedSectionNames, $slugger, $dashboardController);
                $isCurrentSectionGranted = $this->authorizationChecker->isGranted($currentSection->getPermission(), $currentSection);
                if ($isCurrentSectionGranted) {
                    $sections[] = $currentSection;
                }

                continue;
            }

            if (!$widgetOrSection instanceof WidgetInterface) {
                throw new \InvalidArgumentException(sprintf('The "%s::configureWidgets()" method must only return objects that implement "%s" or instances of "%s" ("%s" given).', $dashboardController::class, WidgetInterface::class, WidgetSection::class, get_debug_type($widgetOrSection)));
            }

            $widgetDto = clone $widgetOrSection->getAsDto();
            $this->configureWidgetName($widgetDto, ++$position, $usedWidgetNames, $slugger, $dashboardController);
            $this->assertWidgetCanBeLoadedSeparately($widgetDto, $dashboardController);

            if (!$isCurrentSectionGranted || !$this->authorizationChecker->isGranted($widgetDto->getPermission(), $widgetDto)) {
                $deniedNames[] = (string) $widgetDto->getName();

                continue;
            }

            if (WidgetPlacement::Toolbar === $widgetDto->getPlacement()) {
                $toolbarWidgets[] = $widgetDto;

                continue;
            }

            $currentSection->addWidget($widgetDto);
        }

        $sections = array_values(array_filter($sections, static fn (WidgetSectionDto $section): bool => [] !== $section->getWidgets()));

        return new WidgetCollection($sections, $toolbarWidgets, $deniedNames);
    }

    /**
     * @param array<string, true> $usedSectionNames
     */
    private function configureSectionName(WidgetSectionDto $sectionDto, array &$usedSectionNames, AsciiSlugger $slugger, DashboardControllerInterface $dashboardController): void
    {
        $name = $sectionDto->getName();
        $label = $sectionDto->getLabel();
        if (null === $name && \is_string($label) && '' !== $slug = $slugger->slug($label)->lower()->toString()) {
            $name = $slug;
        }

        if (null === $name) {
            return;
        }

        if (isset($usedSectionNames[$name])) {
            throw new \LogicException(sprintf('The "%s::configureWidgets()" method defines two widget sections with the same name ("%s"). The name of a section is, by default, the slug of its label; call the "setName()" method on one of them to give it a different name.', $dashboardController::class, $name));
        }

        $usedSectionNames[$name] = true;
        $sectionDto->setName($name);
    }

    /**
     * @param array<string, true> $usedWidgetNames
     */
    private function configureWidgetName(WidgetDto $widgetDto, int $position, array &$usedWidgetNames, AsciiSlugger $slugger, DashboardControllerInterface $dashboardController): void
    {
        $name = $widgetDto->getName();
        $label = $widgetDto->getLabel();
        if (null === $name && \is_string($label) && '' !== $slug = $slugger->slug($label)->lower()->toString()) {
            $name = $slug;
        }

        if (null === $name) {
            // the position of a widget changes when other widgets are added, removed or hidden, so it
            // can't identify the widgets whose contents are requested later or stored in the cache
            if ($widgetDto->isLazy() || null !== $widgetDto->getCacheTtl() || null !== $widgetDto->getRefreshInterval()) {
                throw new \LogicException(sprintf('The widget "%s" (in position %d of "%s::configureWidgets()") uses "setLazy()", "setCache()" or "setRefreshInterval()", so it needs a stable name, but its name can\'t be generated because its label is empty or not a string. Call its "setName()" method to set it.', (string) $widgetDto->getWidgetFqcn(), $position, $dashboardController::class));
            }

            $name = 'widget-'.$position;
        }

        if (isset($usedWidgetNames[$name])) {
            throw new \LogicException(sprintf('The "%s::configureWidgets()" method defines two widgets with the same name ("%s"). The name of a widget is, by default, the slug of its label; call the "setName()" method on one of them to give it a different name.', $dashboardController::class, $name));
        }

        $usedWidgetNames[$name] = true;
        $widgetDto->setName($name);
    }

    private function assertWidgetCanBeLoadedSeparately(WidgetDto $widgetDto, DashboardControllerInterface $dashboardController): void
    {
        if (!$widgetDto->isLazy() && null === $widgetDto->getRefreshInterval()) {
            return;
        }

        if (!method_exists($dashboardController, self::RENDER_WIDGET_METHOD)) {
            throw new \LogicException(sprintf('The widget "%s" uses "setLazy()" or "setRefreshInterval()", which load the widget contents in a separate request served by the "%s()" method of the dashboard controller. The "%s" class doesn\'t have that method because it doesn\'t extend "%s". Extend that class or remove those options from the widget.', (string) $widgetDto->getName(), self::RENDER_WIDGET_METHOD, $dashboardController::class, 'EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController'));
        }
    }
}
