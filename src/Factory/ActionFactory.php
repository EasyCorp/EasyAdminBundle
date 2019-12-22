<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

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
     * @return ActionDto[]
     */
    public function create(array $actionsConfig): array
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $defaultTranslationDomain = $applicationContext->getI18n()->getTranslationDomain();

        $builtActions = [];
        foreach ($actionsConfig as $actionConfig) {
            $actionDto = $actionConfig->getAsDto();
            if (false === $this->authChecker->isGranted(Permission::EA_VIEW_ACTION, $actionDto)) {
                continue;
            }

            $generatedActionUrl = $this->generateActionUrl($applicationContext, $actionDto);
            $translatedActionLabel = $this->translator->trans($actionDto->getLabel(), $actionDto->getTranslationParams(), $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);
            $translatedActionHtmlTitle = $this->translator->trans($actionDto->getLinkTitleAttribute(), $actionDto->getTranslationParams(), $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);

            $defaultTemplatePath = $applicationContext->getTemplatePath($actionDto->get('templateName'));

            $builtActions[] = $actionDto->with([
                'label' => $translatedActionLabel,
                'linkUrl' => $generatedActionUrl,
                'linkTitleAttribute' => $translatedActionHtmlTitle,
                'resolvedTemplatePath' => $actionDto->get('templatePath') ?? $defaultTemplatePath,
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
}
