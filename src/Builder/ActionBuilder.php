<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Builder;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfig;
use EasyCorp\Bundle\EasyAdminBundle\Context\ActionContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ActionBuilder
{
    private $isBuilt;
    /** @var ActionContext[] */
    private $builtActions;
    /** @var ActionConfig[] */
    private $actionConfigs;
    private $authChecker;
    private $urlGenerator;
    private $translator;
    private $applicationContextProvider;

    public function __construct(ApplicationContextProvider $applicationContextProvider, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->authChecker = $authChecker;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function addItem(ActionConfig $actionConfig): self
    {
        $this->actionConfigs[] = $actionConfig;
        $this->resetBuiltActions();

        return $this;
    }

    /**
     * @param ActionConfig[] $actionConfigs
     */
    public function setItems(array $actionConfigs): self
    {
        $this->actionConfigs = $actionConfigs;
        $this->resetBuiltActions();

        return $this;
    }

    /**
     * @return ActionContext[]
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

        foreach ($this->actionConfigs as $actionConfig) {
            $actionContext = $actionConfig->getAsValueObject();

            if (!$this->authChecker->isGranted($actionContext->getPermission())) {
                continue;
            }

            $generatedActionUrl = $this->generateActionUrl($actionContext);
            $translatedActionLabel = $this->translator->trans($actionContext->getLabel(), $actionContext->getTranslationParameters(), $actionContext->getTranslationDomain());
            $translatedActionHtmlTitle = $this->translator->trans($actionContext->getHtmlTitle(), $actionContext->getTranslationParameters(), $actionContext->getTranslationDomain());

            $this->builtActions[] = $actionContext->withProperties([
                'htmlTitle' => $translatedActionHtmlTitle,
                'label' => $translatedActionLabel,
                'url' => $generatedActionUrl,
            ]);
        }
    }

    private function generateActionUrl(ActionContext $actionContext): string
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $requestParameters = $applicationContext->getRequest()->query->all();

        if (null !== $routeName = $actionContext->getRouteName()) {
            $routeParameters = array_merge($actionContext->getRouteParameters(), [
                'page' => $applicationContext->getPage(),
                'id' => $applicationContext->getEntity()->getIdValue(),
            ]);

            return $this->urlGenerator->generate($routeName, $routeParameters);
        }

        if ('index' !== $actionContext->getMethodName()) {
            $routeParameters = array_merge($requestParameters, [
                'page' => $applicationContext->getPage(),
                'id' => $applicationContext->getEntity()->getIdValue(),
            ]);

            return $this->urlGenerator->generate($applicationContext->getDashboardRouteName(), $routeParameters);
        }

        // for the 'index' action, try to use the 'referer' value if it exists
        if ($applicationContext->getRequest()->query->has('referer')) {
            return urldecode($applicationContext->getRequest()->query->has('referer'));
        }

        return $this->urlGenerator->generate($applicationContext->getDashboardRouteName(), array_merge($requestParameters, ['page' => 'index']));
    }
}
