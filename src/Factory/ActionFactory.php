<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\EntityCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Action\ActionsExtensionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionGroupDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Translation\TranslatableMessageBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonStyle;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use function Symfony\Component\Translation\t;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionFactory
{
    /**
     * @param iterable<ActionsExtensionInterface> $actionsExtensions
     */
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly AuthorizationCheckerInterface $authChecker,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly ?CsrfTokenManagerInterface $csrfTokenManager = null,
        private readonly iterable $actionsExtensions = [],
    ) {
    }

    public function processEntityActions(EntityDto $entityDto, ActionConfigDto $actionsDto): void
    {
        $currentPage = $this->adminContextProvider->getContext()->getCrud()->getCurrentPage();
        $processedItems = [];

        foreach ($actionsDto->getActions()->all() as $item) {
            if ($item instanceof ActionDto) {
                if (!$item->isEntityAction()) {
                    continue;
                }

                if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => $item, 'entity' => $entityDto])) {
                    continue;
                }

                if (false === $item->isDisplayed($entityDto)) {
                    continue;
                }

                // if CSS class hasn't been overridden, apply the default ones
                if ('' === $item->getCssClass()) {
                    $defaultCssClass = 'action-'.$item->getName();
                    $item->setCssClass($defaultCssClass);
                }

                // these are the additional custom CSS classes defined via addCssClass()
                // which are always appended to the CSS classes (default ones or custom ones)
                if ('' !== $addedCssClass = $item->getAddedCssClass()) {
                    $item->setCssClass($item->getCssClass().' '.$addedCssClass);
                }

                $processedItems[$item->getName()] = $this->processAction($currentPage, $item, $entityDto);
            } elseif ($item instanceof ActionGroupDto) {
                if (!$item->isEntityAction()) {
                    continue;
                }

                if (false === $item->isDisplayed($entityDto)) {
                    continue;
                }

                $processedGroup = $this->processActionGroup($currentPage, $item, $entityDto);
                // only add the group if it has at least one action (ignoring dividers and headers)
                // (actions are removed when processing the group if the user doesn't have permission to execute them)
                if ([] !== $processedGroup->getActions()) {
                    $processedItems[$item->getName()] = $processedGroup;
                }
            }
        }

        if ($actionsDto->getUseAutomaticOrdering()) {
            $processedItems = $this->sortActionsByPriority($processedItems);
        }

        $entityDto->setActions(ActionCollection::new($processedItems));
    }

    public function processGlobalActions(?ActionConfigDto $actionsDto = null): ActionCollection
    {
        if (null === $actionsDto) {
            $actionsDto = $this->adminContextProvider->getContext()->getCrud()->getActionsConfig();
        }

        $currentPage = $this->adminContextProvider->getContext()->getCrud()->getCurrentPage();
        $processedItems = [];

        foreach ($actionsDto->getActions()->all() as $item) {
            if ($item instanceof ActionDto) {
                if (!$item->isGlobalAction() && !$item->isBatchAction()) {
                    continue;
                }

                if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => $item, 'entity' => null])) {
                    continue;
                }

                if (false === $item->isDisplayed()) {
                    continue;
                }

                if (Crud::PAGE_INDEX !== $currentPage && $item->isBatchAction()) {
                    throw new \RuntimeException(sprintf('Batch actions can be added only to the "index" page, but the "%s" batch action is defined in the "%s" page.', $item->getName(), $currentPage));
                }

                // if CSS class hasn't been overridden, apply the default ones
                if ('' === $item->getCssClass()) {
                    $item->setCssClass('action-'.$item->getName());
                }

                // these are the additional custom CSS classes defined via addCssClass()
                // which are always appended to the CSS classes (default ones or custom ones)
                if ('' !== $addedCssClass = $item->getAddedCssClass()) {
                    $item->setCssClass($item->getCssClass().' '.$addedCssClass);
                }

                $processedItems[$item->getName()] = $this->processAction($currentPage, $item);
            } elseif ($item instanceof ActionGroupDto) {
                if (!$item->isGlobalAction() && !$item->isBatchAction()) {
                    continue;
                }

                if (false === $item->isDisplayed()) {
                    continue;
                }

                if (Crud::PAGE_INDEX !== $currentPage && $item->isBatchAction()) {
                    throw new \RuntimeException(sprintf('Batch action groups can be added only to the "index" page, but the "%s" batch action group is defined in the "%s" page.', $item->getName(), $currentPage));
                }

                $processedGroup = $this->processActionGroup($currentPage, $item);
                // only add the group if it has at least one action (ignoring dividers and headers)
                // (actions are removed when processing the group if the user doesn't have permission to execute them)
                if ([] !== $processedGroup->getActions()) {
                    $processedItems[$item->getName()] = $processedGroup;
                }
            }
        }

        if ($actionsDto->getUseAutomaticOrdering()) {
            $processedItems = $this->sortActionsByPriority($processedItems);
        }

        return ActionCollection::new($processedItems);
    }

    public function processGlobalActionsAndEntityActionsForAll(EntityCollection $entityDtos, ActionConfigDto $actionConfigDto): ActionCollection
    {
        foreach ($entityDtos as $entityDto) {
            $this->processEntityActions($entityDto, clone $actionConfigDto);
        }

        return $this->processGlobalActions($actionConfigDto);
    }

    private function processAction(string $pageName, ActionDto $actionDto, ?EntityDto $entityDto = null): ActionDto
    {
        $adminContext = $this->adminContextProvider->getContext();
        $translationDomain = $adminContext->getI18n()->getTranslationDomain();
        $defaultTranslationParameters = $adminContext->getI18n()->getTranslationParameters();

        $actionDto->setHtmlAttribute('data-action-name', $actionDto->getName());

        $this->processActionLabel($actionDto, $entityDto, $translationDomain, $defaultTranslationParameters);

        $defaultTemplatePath = $adminContext->getTemplatePath('crud/action');
        $actionDto->setTemplatePath($actionDto->getTemplatePath() ?? $defaultTemplatePath);

        $actionDto->setLinkUrl($this->generateActionUrl($adminContext->getRequest(), $actionDto, $entityDto));

        if (!$actionDto->isGlobalAction() && \in_array($pageName, [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            // these actions are given the 'form' HTML attribute so when they are clicked, they submit
            // the form that edits/creates the entity; but, for custom actions rendered as forms (this is rare)
            // they use their own form (where the 'action' is the action URL) instead of the entity edit/new form
            if (!$actionDto->isRenderedAsForm()) {
                $actionDto->setHtmlAttribute('form', sprintf('%s-%s-form', $pageName, $entityDto->getName()));
            }
        }

        if (Action::DELETE === $actionDto->getName()) {
            $actionDto->addHtmlAttributes([
                'formaction' => $this->adminUrlGenerator->setController($adminContext->getCrud()->getControllerFqcn())->setAction(Action::DELETE)->setEntityId($entityDto->getPrimaryKeyValue())->generateUrl(),
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-delete',
            ]);
        }

        if ($actionDto->isBatchAction()) {
            $actionDto->addHtmlAttributes([
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-batch-action',
                'data-action-csrf-token' => $this->csrfTokenManager?->getToken('ea-batch-action-'.$actionDto->getName()),
                'data-action-batch' => 'true',
                'data-entity-fqcn' => $adminContext->getCrud()->getEntityFqcn(),
                'data-action-url' => $actionDto->getLinkUrl(),
            ]);
        }

        return $actionDto;
    }

    /**
     * @param array<string, mixed> $defaultTranslationParameters
     */
    private function processActionLabel(ActionDto $actionDto, ?EntityDto $entityDto, string $translationDomain, array $defaultTranslationParameters): void
    {
        $label = $actionDto->getLabel();
        $htmlTitle = trim($actionDto->getHtmlAttributes()['title'] ?? '');

        // FALSE means that action doesn't show a visible label in the interface;
        // add an HTML 'title' attribute (unless the user defined one explicitly) to
        // improve accessibility and show the action name on mouse hover
        if (false === $label && '' === $htmlTitle) {
            $actionDto->setHtmlAttribute('title', $actionDto->getName());

            return;
        }

        if (\is_callable($label) && $label instanceof \Closure) {
            $label = \call_user_func_array($label, array_filter([$entityDto?->getInstance()], static fn ($item): bool => null !== $item));

            if (!\is_string($label) && !$label instanceof TranslatableInterface) {
                throw new \RuntimeException(sprintf('The callable used to define the label of the "%s" action label %s must return a string or a %s instance but it returned a(n) "%s" value instead.', $actionDto->getName(), null !== $entityDto ? 'in the "'.$entityDto->getName().'" entity' : '', TranslatableInterface::class, \gettype($label)));
            }
        }

        $translationParameters = array_merge($defaultTranslationParameters, $actionDto->getTranslationParameters());

        if ($label instanceof TranslatableInterface) {
            $actionDto->setLabel(TranslatableMessageBuilder::withParameters($label, $translationParameters));
        } else {
            $translatableActionLabel = (null === $label || '' === $label || false === $label)
                ? $label
                : t($label, $translationParameters, $translationDomain);
            $actionDto->setLabel($translatableActionLabel);
        }
    }

    private function generateActionUrl(Request $request, ActionDto $actionDto, ?EntityDto $entityDto = null): string
    {
        $entityInstance = $entityDto?->getInstance();

        if (null !== $url = $actionDto->getUrl()) {
            if (\is_callable($url)) {
                return null !== $entityDto ? $url($entityInstance) : $url();
            }

            return $url;
        }

        if (null !== $routeName = $actionDto->getRouteName()) {
            $routeParameters = $actionDto->getRouteParameters();
            if (\is_callable($routeParameters) && null !== $entityInstance) {
                $routeParameters = $routeParameters($entityInstance);
            }

            return $this->adminUrlGenerator->unsetAllExcept(EA::FILTERS, EA::PAGE, EA::QUERY, EA::SORT)->setRoute($routeName, $routeParameters)->generateUrl();
        }

        $requestParameters = [
            // when using pretty URLs, the data is in the request attributes instead of the query string
            EA::CRUD_CONTROLLER_FQCN => $request->attributes->get(EA::CRUD_CONTROLLER_FQCN) ?? $request->query->get(EA::CRUD_CONTROLLER_FQCN),
            EA::CRUD_ACTION => $actionDto->getCrudActionName(),
        ];

        if (\in_array($actionDto->getName(), [Action::INDEX, Action::NEW, Action::SAVE_AND_ADD_ANOTHER], true)) {
            $requestParameters[EA::ENTITY_ID] = null;
        } elseif (null !== $entityDto) {
            $requestParameters[EA::ENTITY_ID] = $entityDto->getPrimaryKeyValueAsString();
        }

        $urlParametersToKeep = [EA::FILTERS, EA::QUERY, EA::SORT, EA::BATCH_ACTION_CSRF_TOKEN, EA::BATCH_ACTION_ENTITY_IDS, EA::BATCH_ACTION_NAME, EA::BATCH_ACTION_URL];
        // when creating a new entity, keeping the selected page number is usually confusing:
        // 1. the user filters/searches/sorts/paginates the results and then creates a new entity
        // 2. if we keep the page number, when the backend returns to the listing, it's very probable
        //    that the user doesn't see the new entity, so they might think that it wasn't created
        // 3. if we keep the other parameters, it's probable that the new entity is shown (sometimes it won't)
        if (Action::NEW !== $actionDto->getName()) {
            $urlParametersToKeep[] = EA::PAGE;
        }

        return $this->adminUrlGenerator->unsetAllExcept(...$urlParametersToKeep)->setAll($requestParameters)->generateUrl();
    }

    /**
     * @param array<string, ActionDto|ActionGroupDto> $actions
     *
     * @return array<string, ActionDto|ActionGroupDto>
     */
    private function sortActionsByPriority(array $actions): array
    {
        $indexed = [];
        $index = 0;
        foreach ($actions as $name => $action) {
            $indexed[] = [
                'name' => $name,
                'action' => $action,
                'index' => $index++,
            ];
        }

        /**
         * @param array{name: string, action: ActionDto|ActionGroupDto, index: int} $a
         * @param array{name: string, action: ActionDto|ActionGroupDto, index: int} $b
         */
        usort($indexed, function (array $a, array $b): int {
            $priorityA = $this->getActionPriority($a['action']);
            $priorityB = $this->getActionPriority($b['action']);

            $primaryComparison = $priorityB <=> $priorityA;
            if (0 !== $primaryComparison) {
                return $primaryComparison;
            }

            // if actions have the same priority, keep the original order
            return $a['index'] <=> $b['index'];
        });

        $sorted = [];
        foreach ($indexed as $item) {
            $sorted[$item['name']] = $item['action'];
        }

        return $sorted;
    }

    private function getActionPriority(ActionDto|ActionGroupDto $item): int
    {
        $baseWeight = ButtonStyle::Solid === $item->getStyle() ? 1000 : 0;

        $variant = $item->getVariant();
        $variantWeight = match ($variant) {
            ButtonVariant::Primary => 100,
            ButtonVariant::Default => 90,
            ButtonVariant::Success => 80,
            ButtonVariant::Warning => 70,
            ButtonVariant::Danger => 60,
        };

        return $baseWeight + $variantWeight;
    }

    private function processActionGroup(string $pageName, ActionGroupDto $groupDto, ?EntityDto $entityDto = null): ActionGroupDto
    {
        if (null !== $mainAction = $groupDto->getMainAction()) {
            $processedMainAction = $this->processAction($pageName, $mainAction, $entityDto);
            $groupDto->setMainAction($processedMainAction);
        }

        $processedActions = [];
        foreach ($groupDto->getItems() as $item) {
            if ($item instanceof ActionDto) {
                if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => $item, 'entity' => $entityDto])) {
                    continue;
                }

                if (false === $item->isDisplayed($entityDto)) {
                    continue;
                }

                $processedAction = $this->processAction($pageName, $item, $entityDto);
                $processedActions[] = $processedAction;
            } else {
                // keep dividers and headers as-is
                $processedActions[] = $item;
            }
        }

        // create a new group DTO with the processed actions
        $newGroupDto = clone $groupDto;
        $newGroupDto->setActions([]);
        foreach ($processedActions as $item) {
            if ($item instanceof ActionDto) {
                $newGroupDto->addAction($item);
            } elseif (\is_array($item) && isset($item['type'])) {
                if ('divider' === $item['type']) {
                    $newGroupDto->addDivider();
                } elseif ('header' === $item['type'] && isset($item['content'])) {
                    $newGroupDto->addHeader($item['content']);
                }
            }
        }

        /** @var AdminContext $adminContext */
        $adminContext = $this->adminContextProvider->getContext();
        $defaultTemplatePath = $adminContext->getTemplatePath('crud/action_group');
        $newGroupDto->setTemplatePath($groupDto->getTemplatePath() ?? $defaultTemplatePath);

        $newGroupDto->setCssClass('btn');
        $newGroupDto->setHtmlAttribute('data-action-group-name', $newGroupDto->getName());

        return $newGroupDto;
    }

    /**
     * Builds the complete actions configuration in this order:
     * Dashboard action config / Default action config > CRUD controller action config > Action Extensions.
     */
    public function buildActionsConfig(DashboardControllerInterface $dashboardController, ?CrudControllerInterface $crudController, AdminContext $context, ?string $pageName): ActionConfigDto
    {
        if (null === $crudController) {
            return new ActionConfigDto();
        }

        $defaultActionConfig = $dashboardController->configureActions();
        $actions = $crudController->configureActions($defaultActionConfig);

        $this->applyExtensions($actions, $context);

        return $actions->getAsDto($pageName);
    }

    /**
     * Applies all registered action extensions to the original Actions configuration
     * so they can modify it adding/updating/removing actions.
     */
    private function applyExtensions(Actions $actions, AdminContext $context): void
    {
        foreach ($this->actionsExtensions as $extension) {
            if (!$extension->supports($context)) {
                continue;
            }

            $extension->extend($actions, $context);
        }
    }
}
