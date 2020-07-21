<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use function Symfony\Component\String\u;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionFactory
{
    private $adminContextProvider;
    private $dashboardRegistry;
    private $authChecker;
    private $translator;
    private $urlGenerator;
    private $crudUrlGenerator;

    public function __construct(AdminContextProvider $adminContextProvider, DashboardControllerRegistry $dashboardRegistry, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator, CrudUrlGenerator $crudUrlGenerator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->dashboardRegistry = $dashboardRegistry;
        $this->authChecker = $authChecker;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->crudUrlGenerator = $crudUrlGenerator;
    }

    public function processEntityActions(EntityDto $entityDto, ActionConfigDto $actionsDto): void
    {
        $currentPage = $this->adminContextProvider->getContext()->getCrud()->getCurrentPage();
        $entityActions = [];
        foreach ($actionsDto->getActions()->all() as $actionDto) {
            if (!$actionDto->isEntityAction()) {
                continue;
            }

            if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, $actionDto)) {
                continue;
            }

            if (false === $actionDto->shouldBeDisplayedFor($entityDto)) {
                continue;
            }

            $entityActions[] = $this->processAction($currentPage, $actionDto, $entityDto);
        }

        $entityDto->setActions(ActionCollection::new($entityActions));
    }

    public function processGlobalActions(ActionConfigDto $actionsDto): ActionCollection
    {
        $currentPage = $this->adminContextProvider->getContext()->getCrud()->getCurrentPage();
        $globalActions = [];
        foreach ($actionsDto->getActions()->all() as $actionDto) {
            if (!$actionDto->isGlobalAction()) {
                continue;
            }

            // TODO: remove this when we reenable "batch actions"
            if ($actionDto->isBatchAction()) {
                throw new \RuntimeException(sprintf('Batch actions are not supported yet, but we\'ll add support for them very soon. Meanwhile, remove the "%s" batch action from the "%s" page.', $actionDto->getName(), $currentPage));
            }

            if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, $actionDto)) {
                continue;
            }

            if (Crud::PAGE_INDEX !== $currentPage && $actionDto->isBatchAction()) {
                throw new \RuntimeException(sprintf('Batch actions can be added only to the "index" page, but the "%s" batch action is defined in the "%s" page.', $actionDto->getName(), $currentPage));
            }

            $globalActions[] = $this->processAction($currentPage, $actionDto);
        }

        return ActionCollection::new($globalActions);
    }

    private function processAction(string $pageName, ActionDto $actionDto, ?EntityDto $entityDto = null): ActionDto
    {
        $adminContext = $this->adminContextProvider->getContext();
        $dashboardControllerFqcn = $adminContext->getDashboardControllerFqcn();
        $adminContextId = $this->dashboardRegistry->getContextIdByControllerFqcn($dashboardControllerFqcn);
        $translationDomain = $adminContext->getI18n()->getTranslationDomain();
        $defaultTranslationParameters = $adminContext->getI18n()->getTranslationParameters();
        $currentPage = $adminContext->getCrud()->getCurrentPage();

        if (false === $actionDto->getLabel()) {
            $actionDto->setHtmlAttributes(array_merge(['title' => $actionDto->getName()], $actionDto->getHtmlAttributes()));
        } else {
            $uLabel = u($actionDto->getLabel());
            // labels with this prefix are considered internal and must be translated
            // with 'EasyAdminBundle' translation domain, regardlesss of the backend domain
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

        $actionDto->setLinkUrl($this->generateActionUrl($adminContextId, $currentPage, $adminContext->getRequest(), $actionDto, $entityDto));

        if (!$actionDto->isGlobalAction() && \in_array($pageName, [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            $actionDto->setHtmlAttribute('form', sprintf('%s-%s-form', $pageName, $entityDto->getName()));
        }

        return $actionDto;
    }

    private function generateActionUrl(string $adminContextId, string $currentAction, Request $request, ActionDto $actionDto, ?EntityDto $entityDto = null): string
    {
        if (null !== $url = $actionDto->getUrl()) {
            if (\is_callable($url)) {
                return $url($entityDto->getInstance());
            }

            return $url;
        }

        if (null !== $routeName = $actionDto->getRouteName()) {
            $routeParameters = $actionDto->getRouteParameters();
            if (\is_callable($routeParameters) && null !== $entityInstance = $entityDto->getInstance()) {
                $routeParameters = $routeParameters($entityInstance);
            }

            $routeParameters = array_merge(['eaContext' => $adminContextId], $routeParameters);

            return $this->urlGenerator->generate($routeName, $routeParameters);
        }

        $requestParameters = [
            'crudId' => $request->query->get('crudId'),
            'crudAction' => $actionDto->getCrudActionName(),
            'referrer' => $this->generateReferrerUrl($request, $actionDto, $currentAction),
        ];

        if (\in_array($actionDto->getName(), [Action::INDEX, Action::NEW], true)) {
            $requestParameters['entityId'] = null;
        } elseif (null !== $entityDto) {
            $requestParameters['entityId'] = $entityDto->getPrimaryKeyValueAsString();
        }

        return $this->crudUrlGenerator->build($requestParameters)->generateUrl();
    }

    private function generateReferrerUrl(Request $request, ActionDto $actionDto, string $currentAction): ?string
    {
        $nextAction = $actionDto->getName();

        if (Action::DETAIL === $currentAction) {
            if (Action::EDIT === $nextAction) {
                return $this->crudUrlGenerator->build()->removeReferrer()->generateUrl();
            }
        }

        if (Action::INDEX === $currentAction) {
            return $this->crudUrlGenerator->build()->removeReferrer()->generateUrl();
        }

        if (Action::NEW === $currentAction) {
            return null;
        }

        $referrer = $request->get('referrer');
        $referrerParts = parse_url($referrer);
        parse_str($referrerParts['query'] ?? '', $referrerQueryStringVariables);
        $referrerCrudAction = $referrerQueryStringVariables['crudAction'] ?? null;

        if (Action::EDIT === $currentAction) {
            if (\in_array($referrerCrudAction, [Action::INDEX, Action::DETAIL], true)) {
                return $referrer;
            }
        }

        return $this->crudUrlGenerator->build()->removeReferrer()->generateUrl();
    }
}
