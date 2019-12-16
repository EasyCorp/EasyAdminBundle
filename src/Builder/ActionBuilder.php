<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Builder\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ActionBuilder implements ItemCollectionBuilderInterface
{
    private $isBuilt;
    /** @var ActionDto[] */
    private $builtActions;
    /** @var Action[] */
    private $actionConfigs;
    private $applicationContextProvider;
    private $authChecker;
    private $translator;
    private $urlGenerator;
    private $crudUrlGenerator;

    public function __construct(ApplicationContextProvider $applicationContextProvider, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator, CrudUrlGenerator $crudUrlGenerator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->authChecker = $authChecker;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->crudUrlGenerator = $crudUrlGenerator;
    }

    /**
     * @param Action $actionConfig
     */
    public function addItem($actionConfig): ItemCollectionBuilderInterface
    {
        $this->actionConfigs[] = $actionConfig;
        $this->resetBuiltActions();

        return $this;
    }

    /**
     * @param Action[] $actionConfigs
     */
    public function setItems(array $actionConfigs): ItemCollectionBuilderInterface
    {
        $this->actionConfigs = $actionConfigs;
        $this->resetBuiltActions();

        return $this;
    }

    /**
     * @return ActionDto[]
     */
    public function build(): array
    {
        if (!$this->isBuilt) {
            $this->buildActions();
            $this->isBuilt = true;
        }

        return $this->builtActions;
    }

    private function resetBuiltActions(): void
    {
        $this->builtActions = [];
        $this->isBuilt = false;
    }

    private function buildActions(): void
    {
        $this->resetBuiltActions();

        $applicationContext = $this->applicationContextProvider->getContext();
        $defaultTranslationDomain = $applicationContext->getI18n()->getTranslationDomain();

        foreach ($this->actionConfigs as $actionConfig) {
            $actionDto = $actionConfig->getAsDto();
            if (false === $this->authChecker->isGranted(Permission::EA_VIEW_ACTION, $actionDto)) {
                continue;
            }

            $generatedActionUrl = $this->generateActionUrl($applicationContext, $actionDto);
            $translatedActionLabel = $this->translator->trans($actionDto->getLabel(), $actionDto->getTranslationParams(), $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);
            $translatedActionHtmlTitle = $this->translator->trans($actionDto->getLinkTitleAttribute(), $actionDto->getTranslationParams(), $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);

            $defaultTemplatePath = $applicationContext->getTemplatePath($actionDto->get('templateName'));

            $this->builtActions[] = $actionDto->with([
                'label' => $translatedActionLabel,
                'linkUrl' => $generatedActionUrl,
                'linkTitleAttribute' => $translatedActionHtmlTitle,
                'resolvedTemplatePath' => $actionDto->get('templatePath') ?? $defaultTemplatePath,
            ]);
        }
    }

    private function generateActionUrl(ApplicationContext $applicationContext, ActionDto $actionDto): string
    {
        $requestParameters = $applicationContext->getRequest()->query->all();

        if (null !== $routeName = $actionDto->getRouteName()) {
            $routeParameters = array_merge($requestParameters, $actionDto->getRouteParameters());

            return $this->urlGenerator->generate($routeName, $routeParameters);
        }

        if ('index' !== $crudActionName = $actionDto->getCrudActionName()) {
            return $this->crudUrlGenerator->generate(['crudAction' => $crudActionName]);
        }

        // for the 'index' action, try to use the 'referrer' value if it exists
        if ($applicationContext->getRequest()->query->has('referrer')) {
            return urldecode($applicationContext->getRequest()->query->get('referrer'));
        }

        return $this->crudUrlGenerator->generate(['crudAction' => 'index']);
    }
}
