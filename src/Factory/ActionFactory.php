<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
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

    /**
     * @param Action[] $actionsConfig
     *
     * @return ActionDto[]
     */
    public function create(array $actionsConfig): array
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $defaultTranslationDomain = $applicationContext->getI18n()->getTranslationDomain();
        $currentAction = $applicationContext->getCrud()->getAction();
        $actionUpdateCallables = $applicationContext->getCrud()->getPage()->getActionUpdateCallables();

        $builtActions = [];
        $actionsConfig = $this->preProcessActionsConfig($currentAction, $actionsConfig);
        foreach ($actionsConfig as $actionConfig) {
            $actionName = (string) $actionConfig;
            if (array_key_exists($actionName, $actionUpdateCallables) && null !== $actionUpdateCallables[$actionName]) {
                $actionConfig = call_user_func($actionUpdateCallables[$actionName], $actionConfig);
            }

            $actionDto = $actionConfig->getAsDto();
            if (false === $this->authChecker->isGranted(Permission::EA_VIEW_ACTION, $actionDto)) {
                continue;
            }

            $translatedActionLabel = $this->translator->trans($actionDto->getLabel(), $actionDto->getTranslationParams(), $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);
            $translatedActionHtmlTitle = $this->translator->trans($actionDto->getLinkTitleAttribute(), $actionDto->getTranslationParams(), $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);

            $defaultTemplatePath = $applicationContext->getTemplatePath('crud/action');

            $builtActions[] = $actionDto->with([
                'label' => $translatedActionLabel,
                'linkTitleAttribute' => $translatedActionHtmlTitle,
                'templatePath' => $actionDto->get('templatePath') ?? $defaultTemplatePath,
            ]);
        }

        return $builtActions;
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

    /**
     * @param Action[] $actionsConfig
     *
     * @return Action[]
     */
    private function preProcessActionsConfig(string $currentAction, array $actionsConfig): array
    {
        // fox DX reasons, action config can be just a string with the action name
        foreach ($actionsConfig as $i => $actionConfig) {
            if (\is_string($actionConfig)) {
                $actionsConfig[$i] = $this->createBuiltInAction($currentAction, $actionConfig);
            }
        }

        return $actionsConfig;
    }

    /**
     * The $currentAction is needed because sometimes the same action has different config
     * depending on where it's displayed (to display an icon in 'detail' but not in 'index', etc.)
     */
    private function createBuiltInAction(string $currentAction, string $actionName): Action
    {
        if ('edit' === $actionName) {
            return Action::new('edit', 'action.edit', null)
                ->linkToCrudAction('edit')
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
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('delete' === $actionName) {
            return Action::new('delete', 'action.delete', 'index' === $currentAction ? null : 'fa fa-trash')
                ->linkToCrudAction('delete')
                ->setCssClass('text-danger')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('save' === $actionName) {
            return Action::new('save', 'edit' === $currentAction ? 'action.save' : 'action.create')
                ->linkToCrudAction('edit' === $currentAction ? 'edit' : 'new')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('save-and-continue' === $actionName) {
            return Action::new('save-and-continue', 'edit' === $currentAction ? 'action.save_and_continue' : 'action.create_and_continue')
                ->linkToCrudAction('edit' === $currentAction ? 'edit' : 'new')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if ('save-and-add-another' === $actionName) {
            return Action::new('save-and-add-another', 'action.create_and_add_another')
                ->linkToCrudAction('new')
                ->setTranslationDomain('EasyAdminBundle');
        }

        throw new \InvalidArgumentException(sprintf('The "%s" action is not a built-in action, so you can\'t add or configure it via its name. Either refer to one of the built-in actions or create a custom action called "%s".', $actionName, $actionName));
    }
}
