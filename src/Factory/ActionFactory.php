<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ActionFactory
{
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

    public function create(EntityDto $entityDto, array $actionsConfig): EntityDto
    {
        // TODO: fix this:
        return $entityDto;

        $applicationContext = $this->applicationContextProvider->getContext();
        $defaultTranslationDomain = $applicationContext->getI18n()->getTranslationDomain();
        $currentAction = $applicationContext->getCrud()->getAction();
        $actionUpdateCallables = $applicationContext->getCrud()->getActionUpdateCallables();
        $actionsConfig = $this->preProcessActionsConfig($currentAction, $actionsConfig, $actionUpdateCallables);

        $builtActions = [];
        foreach ($actionsConfig as $actionConfig) {
            $actionDto = $actionConfig->getAsDto();

            if (false === $this->authChecker->isGranted(Permission::EA_VIEW_ACTION, $actionDto)) {
                continue;
            }

            if (false === $actionDto->shouldBeDisplayedFor($entityDto)) {
                continue;
            }

            $translatedActionLabel = $this->translator->trans($actionDto->getLabel(), $actionDto->getTranslationParams(), $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);
            $defaultTemplatePath = $applicationContext->getTemplatePath('crud/action');

            $builtActions[] = $actionDto->with([
                'label' => $translatedActionLabel,
                'templatePath' => $actionDto->get('templatePath') ?? $defaultTemplatePath,
                'linkUrl' => $this->generateActionUrl($applicationContext->getRequest(), $entityDto, $actionDto, $currentAction),
            ]);
        }

        return $entityDto->updateActions($builtActions);
    }

    private function generateActionUrl(Request $request, EntityDto $entityDto, ActionDto $actionDto, string $currentAction): string
    {
        $requestParameters = [
            'crudController' => $request->query->get('crudController'),
            'entityId' => $entityDto->getPrimaryKeyValueAsString(),
            'referrer' => $this->generateReferrerUrl($request, $actionDto, $currentAction),
        ];

        if (null !== $routeName = $actionDto->getRouteName()) {
            $routeParameters = array_merge($request->query->all(), $requestParameters, $actionDto->getRouteParameters());

            return $this->urlGenerator->generate($routeName, $routeParameters);
        }

        $requestParameters = array_merge($requestParameters, [
            'crudAction' => $actionDto->getCrudActionName(),
        ]);

        return $this->crudUrlGenerator->build()->setQueryParams($requestParameters)->generateUrl();
    }

    /**
     * @param Action[] $actionsConfig
     *
     * @return Action[]
     */
    private function preProcessActionsConfig(string $currentAction, array $actionsConfig, array $actionUpdateCallables): array
    {
        foreach ($actionsConfig as $i => $actionConfig) {
            // apply the callables that update certain config options of the action
            $actionName = (string) $actionConfig;
            if (\array_key_exists($actionName, $actionUpdateCallables) && null !== $actionUpdateCallables[$actionName]) {
                $actionConfig = \call_user_func($actionUpdateCallables[$actionName], $actionConfig);
            }

            $actionsConfig[$i] = $actionConfig;
        }

        return $actionsConfig;
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
            if (\in_array($referrerCrudAction, [Action::INDEX, Action::DETAIL])) {
                return $referrer;
            }
        }

        return $this->crudUrlGenerator->build()->removeReferrer()->generateUrl();
    }
}
