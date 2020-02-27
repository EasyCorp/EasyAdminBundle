<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
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
        $applicationContext = $this->applicationContextProvider->getContext();
        $defaultTranslationDomain = $applicationContext->getI18n()->getTranslationDomain();
        $currentAction = $applicationContext->getCrud()->getAction();
        $actionUpdateCallables = $applicationContext->getCrud()->getPage()->getActionUpdateCallables();
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
            // fox DX reasons, action config can be just a string with the action name
            if (\is_string($actionConfig)) {
                $actionConfig = $this->createBuiltInAction($currentAction, $actionConfig);
            }

            // apply the callables that update certain config options of the action
            $actionName = (string) $actionConfig;
            if (\array_key_exists($actionName, $actionUpdateCallables) && null !== $actionUpdateCallables[$actionName]) {
                $actionConfig = \call_user_func($actionUpdateCallables[$actionName], $actionConfig);
            }

            $actionsConfig[$i] = $actionConfig;
        }

        return $actionsConfig;
    }

    /**
     * The $currentAction is needed because sometimes the same action has different config
     * depending on where it's displayed (to display an icon in 'detail' but not in 'index', etc.).
     */
    private function createBuiltInAction(string $currentAction, string $actionName): Action
    {
        if (Action::EDIT === $actionName) {
            return Action::new(Action::EDIT, 'action.edit', null)
                ->linkToCrudAction(Action::EDIT)
                ->setCssClass(Action::DETAIL === $currentAction ? 'btn btn-primary' : '')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::DETAIL === $actionName) {
            return Action::new(Action::DETAIL, 'action.detail')
                ->linkToCrudAction(Action::DETAIL)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::INDEX === $actionName) {
            return Action::new(Action::INDEX, 'action.index')
                ->linkToCrudAction(Action::INDEX)
                ->setCssClass(Action::DETAIL === $currentAction ? 'btn' : '')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::DELETE === $actionName) {
            $cssClass = Action::DETAIL === $currentAction ? 'btn btn-link pr-0 text-danger' : 'text-danger';

            return Action::new(Action::DELETE, 'action.delete', Action::INDEX === $currentAction ? null : 'fa fa-fw fa-trash-o')
                ->linkToCrudAction(Action::DELETE)
                ->setCssClass($cssClass)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::SAVE_AND_RETURN === $actionName) {
            return Action::new(Action::SAVE_AND_RETURN, Action::EDIT === $currentAction ? 'action.save' : 'action.create')
                ->setCssClass('btn btn-primary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Action::EDIT === $currentAction ? Action::EDIT : Action::NEW)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::SAVE_AND_CONTINUE === $actionName) {
            return Action::new(Action::SAVE_AND_CONTINUE, Action::EDIT === $currentAction ? 'action.save_and_continue' : 'action.create_and_continue', 'far fa-edit')
                ->setCssClass('btn btn-secondary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Action::EDIT === $currentAction ? Action::EDIT : Action::NEW)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::SAVE_AND_ADD_ANOTHER === $actionName) {
            return Action::new(Action::SAVE_AND_ADD_ANOTHER, 'action.create_and_add_another')
                ->setCssClass('btn btn-secondary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Action::NEW)
                ->setTranslationDomain('EasyAdminBundle');
        }

        throw new \InvalidArgumentException(sprintf('The "%s" action is not a built-in action, so you can\'t add or configure it via its name. Either refer to one of the built-in actions or create a custom action called "%s".', $actionName, $actionName));
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
