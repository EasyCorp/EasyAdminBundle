<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
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

    public function create(ActionConfigDto $actionConfigDto): ActionConfigDto
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $defaultTranslationDomain = $applicationContext->getI18n()->getTranslationDomain();
        $defaultTranslationParameters = $applicationContext->getI18n()->getTranslationParameters();
        $currentPage = $applicationContext->getCrud()->getCurrentPage();

        $builtActions = [];
        foreach ($actionConfigDto->getActions() as $actionDto) {
            if (false === $this->authChecker->isGranted(Permission::EA_EXECUTE_ACTION, $actionDto)) {
                continue;
            }

            $translationParameters = array_merge($defaultTranslationParameters, $actionDto->getTranslationParameters());
            $translatedActionLabel = $this->translator->trans($actionDto->getLabel(), $translationParameters, $actionDto->getTranslationDomain() ?? $defaultTranslationDomain);
            $defaultTemplatePath = $applicationContext->getTemplatePath('crud/action');

            $builtActions[] = $actionDto->with([
                'label' => $translatedActionLabel,
                'templatePath' => $actionDto->get('templatePath') ?? $defaultTemplatePath,
                'linkUrl' => $this->generateActionUrl($currentPage, $applicationContext->getRequest(), $actionDto),
            ]);
        }

        return $actionConfigDto->updateActions($builtActions);
    }

    public function createForEntity(ActionConfigDto $actionConfigDto, EntityDto $entityDto): EntityDto
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $currentPage = $applicationContext->getCrud()->getCurrentPage();

        $builtActions = [];
        foreach ($actionConfigDto->getActions() as $actionDto) {
            if (!$actionDto->isEntityAction()) {
                continue;
            }

            if (false === $actionDto->shouldBeDisplayedFor($entityDto)) {
                continue;
            }

            if (CrudConfig::PAGE_EDIT === $currentPage) {
                // this is needed because buttons are rendered outside of the <form> element
                $formId = sprintf('edit-%s-form', $entityDto->getName());
                $actionDto = $actionDto->with([
                    'htmlAttributes' => array_merge(['form' => $formId], $actionDto->getHtmlAttributes()),
                ]);
            }

            if (CrudConfig::PAGE_NEW === $currentPage) {
                // this is needed because buttons are rendered outside of the <form> element
                $formId = sprintf('new-%s-form', $entityDto->getName());
                $actionDto = $actionDto->with([
                    'htmlAttributes' => array_merge(['form' => $formId], $actionDto->getHtmlAttributes()),
                ]);
            }

            $builtActions[] = $actionDto->with([
                'linkUrl' => $this->generateActionUrl($currentPage, $applicationContext->getRequest(), $actionDto, $entityDto),
            ]);
        }

        return $entityDto->updateActions($builtActions);
    }

    private function generateActionUrl(string $currentAction, Request $request, ActionDto $actionDto, EntityDto $entityDto = null): string
    {
        $requestParameters = [
            'crudController' => $request->query->get('crudController'),
            'referrer' => $this->generateReferrerUrl($request, $actionDto, $currentAction),
        ];

        if (null !== $entityDto) {
            $requestParameters['entityId'] = $entityDto->getPrimaryKeyValueAsString();
        }

        if (null !== $routeName = $actionDto->getRouteName()) {
            $routeParameters = array_merge($request->query->all(), $requestParameters, $actionDto->getRouteParameters());

            return $this->urlGenerator->generate($routeName, $routeParameters);
        }

        $requestParameters = array_merge($requestParameters, [
            'crudAction' => $actionDto->getCrudActionName(),
        ]);

        return $this->crudUrlGenerator->build()->setQueryParameters($requestParameters)->generateUrl();
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
