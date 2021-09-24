<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use function Symfony\Component\String\u;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionFactory
{
    private $adminContextProvider;
    private $authChecker;
    private $translator;
    private $adminUrlGenerator;
    private $csrfTokenManager;

    public function __construct(AdminContextProvider $adminContextProvider, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator, AdminUrlGenerator $adminUrlGenerator, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->authChecker = $authChecker;
        $this->translator = $translator;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function processEntityActions(EntityDto $entityDto, ActionConfigDto $actionsDto): void
    {
        $currentPage = $this->adminContextProvider->getContext()->getCrud()->getCurrentPage();
        $entityActions = [];
        foreach ($actionsDto->getActions()->all() as $actionDto) {
            if (!$actionDto->isEntityAction()) {
                continue;
            }

            if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => $actionDto, 'entity' => $entityDto])) {
                continue;
            }

            if (false === $actionDto->shouldBeDisplayedFor($entityDto)) {
                continue;
            }

            if ('' === $actionDto->getCssClass()) {
                $defaultCssClass = 'action-'.$actionDto->getName();
                if (Crud::PAGE_INDEX !== $currentPage) {
                    $defaultCssClass .= ' btn';
                }

                $actionDto->setCssClass($defaultCssClass);
            }

            $entityActions[] = $this->processAction($currentPage, $actionDto, $entityDto);
        }

        $entityDto->setActions(ActionCollection::new($entityActions));
    }

    public function processGlobalActions(ActionConfigDto $actionsDto = null): ActionCollection
    {
        if (null === $actionsDto) {
            $actionsDto = $this->adminContextProvider->getContext()->getCrud()->getActionsConfig();
        }

        $currentPage = $this->adminContextProvider->getContext()->getCrud()->getCurrentPage();
        $globalActions = [];
        foreach ($actionsDto->getActions()->all() as $actionDto) {
            if (!$actionDto->isGlobalAction() && !$actionDto->isBatchAction()) {
                continue;
            }

            if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => $actionDto, 'entity' => null])) {
                continue;
            }

            if (Crud::PAGE_INDEX !== $currentPage && $actionDto->isBatchAction()) {
                throw new \RuntimeException(sprintf('Batch actions can be added only to the "index" page, but the "%s" batch action is defined in the "%s" page.', $actionDto->getName(), $currentPage));
            }

            if ('' === $actionDto->getCssClass()) {
                $actionDto->setCssClass('btn action-'.$actionDto->getName());
            }

            $globalActions[] = $this->processAction($currentPage, $actionDto);
        }

        return ActionCollection::new($globalActions);
    }

    private function processAction(string $pageName, ActionDto $actionDto, ?EntityDto $entityDto = null): ActionDto
    {
        $adminContext = $this->adminContextProvider->getContext();
        $translationDomain = $adminContext->getI18n()->getTranslationDomain();
        $defaultTranslationParameters = $adminContext->getI18n()->getTranslationParameters();
        $currentPage = $adminContext->getCrud()->getCurrentPage();

        $actionDto->setHtmlAttribute('data-action-name', $actionDto->getName());

        if (false === $actionDto->getLabel()) {
            $actionDto->setHtmlAttribute('title', $actionDto->getName());
        } else {
            $uLabel = u($actionDto->getLabel());
            // labels with this prefix are considered internal and must be translated
            // with 'EasyAdminBundle' translation domain, regardless of the backend domain
            if ($uLabel->startsWith('__ea__')) {
                $uLabel = $uLabel->after('__ea__');
                $translationDomain = 'EasyAdminBundle';
            }

            $translationParameters = array_merge($defaultTranslationParameters, $actionDto->getTranslationParameters());
            $label = $uLabel->toString();
            $translatedActionLabel = empty($label) ? $label : $this->translator->trans($label, $translationParameters, $translationDomain);
            $actionDto->setLabel($translatedActionLabel);
        }

        $defaultTemplatePath = $adminContext->getTemplatePath('crud/action');
        $actionDto->setTemplatePath($actionDto->getTemplatePath() ?? $defaultTemplatePath);

        $actionDto->setLinkUrl($this->generateActionUrl($currentPage, $adminContext->getRequest(), $actionDto, $entityDto));

        if (!$actionDto->isGlobalAction() && \in_array($pageName, [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            $actionDto->setHtmlAttribute('form', sprintf('%s-%s-form', $pageName, $entityDto->getName()));
        }

        if (Action::DELETE === $actionDto->getName()) {
            $actionDto->addHtmlAttributes([
                'formaction' => $this->adminUrlGenerator->setAction(Action::DELETE)->setEntityId($entityDto->getPrimaryKeyValue())->removeReferrer()->generateUrl(),
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-delete',
            ]);
        }

        if ($actionDto->isBatchAction()) {
            $actionDto->addHtmlAttributes([
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-batch-action',
                'data-action-csrf-token' => $this->csrfTokenManager->getToken('ea-batch-action-'.$actionDto->getName()),
                'data-action-batch' => 'true',
                'data-entity-fqcn' => $adminContext->getCrud()->getEntityFqcn(),
                'data-action-url' => $actionDto->getLinkUrl(),
            ]);
        }

        return $actionDto;
    }

    private function generateActionUrl(string $currentAction, Request $request, ActionDto $actionDto, ?EntityDto $entityDto = null): string
    {
        if (null !== $url = $actionDto->getUrl()) {
            if (\is_callable($url)) {
                return null !== $entityDto ? $url($entityDto->getInstance()) : $url();
            }

            return $url;
        }

        if (null !== $routeName = $actionDto->getRouteName()) {
            $routeParameters = $actionDto->getRouteParameters();
            if (\is_callable($routeParameters) && null !== $entityInstance = $entityDto->getInstance()) {
                $routeParameters = $routeParameters($entityInstance);
            }

            return $this->adminUrlGenerator->unsetAllExcept(EA::MENU_INDEX, EA::SUBMENU_INDEX)->includeReferrer()->setRoute($routeName, $routeParameters)->generateUrl();
        }

        $requestParameters = [
            EA::CRUD_CONTROLLER_FQCN => $request->query->get(EA::CRUD_CONTROLLER_FQCN),
            EA::CRUD_ACTION => $actionDto->getCrudActionName(),
            EA::REFERRER => $this->generateReferrerUrl($request, $actionDto, $currentAction),
        ];

        if (\in_array($actionDto->getName(), [Action::INDEX, Action::NEW, Action::SAVE_AND_ADD_ANOTHER, Action::SAVE_AND_RETURN], true)) {
            $requestParameters[EA::ENTITY_ID] = null;
        } elseif (null !== $entityDto) {
            $requestParameters[EA::ENTITY_ID] = $entityDto->getPrimaryKeyValueAsString();
        }

        return $this->adminUrlGenerator->unsetAllExcept(EA::MENU_INDEX, EA::SUBMENU_INDEX, EA::FILTERS)->setAll($requestParameters)->generateUrl();
    }

    private function generateReferrerUrl(Request $request, ActionDto $actionDto, string $currentAction): ?string
    {
        $nextAction = $actionDto->getName();

        if (Action::DETAIL === $currentAction) {
            if (Action::EDIT === $nextAction) {
                return $this->adminUrlGenerator->removeReferrer()->generateUrl();
            }
        }

        if (Action::INDEX === $currentAction) {
            return $this->adminUrlGenerator->removeReferrer()->generateUrl();
        }

        if (Action::NEW === $currentAction) {
            return null;
        }

        $referrer = $request->get(EA::REFERRER);
        $referrerParts = parse_url($referrer);
        parse_str($referrerParts[EA::QUERY] ?? '', $referrerQueryStringVariables);
        $referrerCrudAction = $referrerQueryStringVariables[EA::CRUD_ACTION] ?? null;

        if (Action::EDIT === $currentAction) {
            if (\in_array($referrerCrudAction, [Action::INDEX, Action::DETAIL], true)) {
                return $referrer;
            }
        }

        return $this->adminUrlGenerator->removeReferrer()->generateUrl();
    }
}
