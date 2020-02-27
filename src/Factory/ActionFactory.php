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

        return $this->crudUrlGenerator->generate($requestParameters);
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
        if ('edit' === $actionName) {
            return Action::new('edit', 'action.edit', null)
                ->linkToCrudAction('edit')
                ->setCssClass('detail' === $currentAction ? 'btn btn-primary' : '')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('detail' === $actionName) {
            return Action::new('detail', 'action.detail')
                ->linkToCrudAction('detail')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('index' === $actionName) {
            return Action::new('index', 'action.index')
                ->linkToCrudAction('index')
                ->setCssClass('detail' === $currentAction ? 'btn' : '')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('delete' === $actionName) {
            $cssClass = 'detail' === $currentAction ? 'btn btn-link pr-0 text-danger' : 'text-danger';

            return Action::new('delete', 'action.delete', 'index' === $currentAction ? null : 'fa fa-fw fa-trash-o')
                ->linkToCrudAction('delete')
                ->setCssClass($cssClass)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('save-and-close' === $actionName) {
            return Action::new('save-and-close', 'edit' === $currentAction ? 'action.save' : 'action.create')
                ->setCssClass('btn btn-primary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction('edit' === $currentAction ? 'edit' : 'new')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('save-and-continue' === $actionName) {
            return Action::new('save-and-continue', 'edit' === $currentAction ? 'action.save_and_continue' : 'action.create_and_continue', 'far fa-edit')
                ->setCssClass('btn btn-secondary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction('edit' === $currentAction ? 'edit' : 'new')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('save-and-add-another' === $actionName) {
            return Action::new('save-and-add-another', 'action.create_and_add_another')
                ->setCssClass('btn btn-secondary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction('new')
                ->setTranslationDomain('EasyAdminBundle');
        }

        throw new \InvalidArgumentException(sprintf('The "%s" action is not a built-in action, so you can\'t add or configure it via its name. Either refer to one of the built-in actions or create a custom action called "%s".', $actionName, $actionName));
    }

    private function generateReferrerUrl(Request $request, ActionDto $actionDto, string $currentAction): ?string
    {
        $nextAction = $actionDto->getName();

        if ('detail' === $currentAction) {
            if ('edit' === $nextAction) {
                return $this->crudUrlGenerator->removeReferrer()->getUrl();
            }
        }

        if ('index' === $currentAction) {
            return $this->crudUrlGenerator->removeReferrer()->getUrl();
        }

        if ('new' === $currentAction) {
            return null;
        }

        $referrer = $request->get('referrer');
        $referrerParts = parse_url($referrer);
        parse_str($referrerParts['query'] ?? '', $referrerQueryStringVariables);
        $referrerCrudAction = $referrerQueryStringVariables['crudAction'] ?? null;

        if ('edit' === $currentAction) {
            if (\in_array($referrerCrudAction, ['index', 'detail'])) {
                return $referrer;
            }
        }

        return $this->crudUrlGenerator->removeReferrer()->getUrl();
    }
}
