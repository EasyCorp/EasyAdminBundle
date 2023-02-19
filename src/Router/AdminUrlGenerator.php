<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminUrlGenerator
{
    private bool $isInitialized = false;
    private AdminContextProvider $adminContextProvider;
    private UrlGeneratorInterface $urlGenerator;
    private DashboardControllerRegistry $dashboardControllerRegistry;
    private ?string $dashboardRoute = null;
    private ?bool $includeReferrer = null;
    private array $routeParameters = [];
    private ?string $currentPageReferrer = null;
    private ?string $customPageReferrer = null;

    public function __construct(AdminContextProvider $adminContextProvider, UrlGeneratorInterface $urlGenerator, DashboardControllerRegistry $dashboardControllerRegistry)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->urlGenerator = $urlGenerator;
        $this->dashboardControllerRegistry = $dashboardControllerRegistry;
    }

    public function setDashboard(string $dashboardControllerFqcn): self
    {
        $this->setRouteParameter(EA::DASHBOARD_CONTROLLER_FQCN, $dashboardControllerFqcn);

        return $this;
    }

    public function setController(string $crudControllerFqcn): self
    {
        $this->setRouteParameter(EA::CRUD_CONTROLLER_FQCN, $crudControllerFqcn);
        $this->unset(EA::ROUTE_NAME);
        $this->unset(EA::ROUTE_PARAMS);

        return $this;
    }

    public function setAction(string $action): self
    {
        $this->setRouteParameter(EA::CRUD_ACTION, $action);
        $this->unset(EA::ROUTE_NAME);
        $this->unset(EA::ROUTE_PARAMS);

        return $this;
    }

    public function setRoute(string $routeName, array $routeParameters = []): self
    {
        $this->unsetAllExcept(EA::DASHBOARD_CONTROLLER_FQCN);
        $this->setRouteParameter(EA::ROUTE_NAME, $routeName);
        $this->setRouteParameter(EA::ROUTE_PARAMS, $routeParameters);

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->setRouteParameter(EA::ENTITY_ID, $entityId);

        return $this;
    }

    public function get(string $paramName)
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        return $this->routeParameters[$paramName] ?? null;
    }

    public function set(string $paramName, $paramValue): self
    {
        if (\in_array($paramName, [EA::MENU_INDEX, EA::SUBMENU_INDEX], true)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.5.0',
                'Using the "%s" query parameter is deprecated. Menu items are now highlighted automatically based on the Request data, so you don\'t have to deal with menu items manually anymore.',
                $paramName,
            );
        }

        $this->setRouteParameter($paramName, $paramValue);

        return $this;
    }

    public function setAll(array $routeParameters): self
    {
        foreach ($routeParameters as $paramName => $paramValue) {
            $this->setRouteParameter($paramName, $paramValue);
        }

        return $this;
    }

    public function unset(string $paramName): self
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        unset($this->routeParameters[$paramName]);

        return $this;
    }

    public function unsetAll(): self
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        $this->routeParameters = [];

        return $this;
    }

    public function unsetAllExcept(string ...$namesOfParamsToKeep): self
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        $this->routeParameters = array_intersect_key($this->routeParameters, array_flip($namesOfParamsToKeep));

        return $this;
    }

    public function includeReferrer(): self
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        $this->includeReferrer = true;

        return $this;
    }

    public function removeReferrer(): self
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        $this->includeReferrer = false;

        return $this;
    }

    public function setReferrer(string $referrer): self
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        $this->includeReferrer = true;
        $this->customPageReferrer = $referrer;

        return $this;
    }

    public function addSignature(bool $addSignature = true): self
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.1.0',
            'EasyAdmin URLs no longer include signatures because they don\'t provide any additional security. Calling the "%s" method has no effect, so you can stop calling it. This method will be removed in future EasyAdmin versions.',
            __METHOD__,
        );

        return $this;
    }

    public function getSignature(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.1.0',
            'EasyAdmin URLs no longer include signatures because they don\'t provide any additional security. Calling the "%s" method will always return an empty string, so you can stop calling it. This method will be removed in future EasyAdmin versions.',
            __METHOD__,
        );

        return '';
    }

    // this method allows to omit the 'generateUrl()' call in templates, making code more concise
    public function __toString(): string
    {
        return $this->generateUrl();
    }

    public function generateUrl(): string
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        if (true === $this->includeReferrer) {
            $this->setRouteParameter(EA::REFERRER, $this->customPageReferrer ?? $this->currentPageReferrer);
        }

        if (false === $this->includeReferrer) {
            $this->unset(EA::REFERRER);
        }

        // this avoids forcing users to always be explicit about the action to execute
        if (null !== $this->get(EA::CRUD_CONTROLLER_FQCN) && null === $this->get(EA::CRUD_ACTION)) {
            $this->set(EA::CRUD_ACTION, Action::INDEX);
        }

        // if the Dashboard FQCN is defined, find its route and use it to override
        // the current route (this is needed to allow generating links to different dashboards)
        if (null !== $dashboardControllerFqcn = $this->get(EA::DASHBOARD_CONTROLLER_FQCN)) {
            if (null === $dashboardRoute = $this->dashboardControllerRegistry->getRouteByControllerFqcn($dashboardControllerFqcn)) {
                throw new \InvalidArgumentException(sprintf('The given "%s" class is not a valid Dashboard controller. Make sure it extends from "%s" or implements "%s".', $dashboardControllerFqcn, AbstractDashboardController::class, DashboardControllerInterface::class));
            }

            $this->dashboardRoute = $dashboardRoute;
            $this->unset(EA::DASHBOARD_CONTROLLER_FQCN);
        }

        // this happens when generating URLs from outside EasyAdmin (AdminContext is null) and
        // no Dashboard FQCN has been defined explicitly
        if (null === $this->dashboardRoute) {
            if ($this->dashboardControllerRegistry->getNumberOfDashboards() > 1) {
                throw new \RuntimeException('When generating admin URLs from outside EasyAdmin or without a related HTTP request (e.g. in tests, console commands, etc.), if your application has more than one Dashboard, you must associate the URL to a specific Dashboard using the "setDashboard()" method.');
            }

            $this->dashboardRoute = $this->dashboardControllerRegistry->getFirstDashboardRoute();
        }

        // if present, remove the suffix of i18n route names (it's a two-letter locale at the end
        // of the route name; e.g. 'dashboard.en' -> remove '.en', 'admin.index.es' -> remove '.es')
        $this->dashboardRoute = preg_replace('~\.\w{2}$~', '', $this->dashboardRoute);

        // this removes any parameter with a NULL value
        $routeParameters = array_filter(
            $this->routeParameters,
            static fn ($parameterValue): bool => null !== $parameterValue
        );
        ksort($routeParameters, \SORT_STRING);

        $context = $this->adminContextProvider->getContext();
        $urlType = null !== $context && false === $context->getAbsoluteUrls() ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_URL;
        $url = $this->urlGenerator->generate($this->dashboardRoute, $routeParameters, $urlType);
        $url = '' === $url ? '?' : $url;

        // this is important to start the generation a each URL from the same initial state
        // otherwise, some parameters used when generating some URL could leak to other URLs
        $this->isInitialized = false;

        return $url;
    }

    private function setRouteParameter(string $paramName, $paramValue): void
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        if (\is_resource($paramValue)) {
            throw new \InvalidArgumentException(sprintf('The value of the "%s" parameter is a PHP resource, which is not supported as a route parameter.', $paramName));
        }

        if (\is_object($paramValue)) {
            if (method_exists($paramValue, '__toString')) {
                $paramValue = (string) $paramValue;
            } else {
                throw new \InvalidArgumentException(sprintf('The object passed as the value of the "%s" parameter must implement the "__toString()" method to allow using its value as a route parameter.', $paramName));
            }
        }

        $this->routeParameters[$paramName] = $paramValue;
    }

    private function initialize(): void
    {
        $this->isInitialized = true;

        $adminContext = $this->adminContextProvider->getContext();

        if (null === $adminContext) {
            $this->dashboardRoute = null;
            $currentRouteParameters = $routeParametersForReferrer = [];
            $this->currentPageReferrer = null;
        } else {
            $this->dashboardRoute = $adminContext->getDashboardRouteName();
            $currentRouteParameters = $routeParametersForReferrer = $adminContext->getRequest()->query->all();
            unset($routeParametersForReferrer[EA::REFERRER]);
            $this->currentPageReferrer = sprintf('%s%s?%s', $adminContext->getRequest()->getBaseUrl(), $adminContext->getRequest()->getPathInfo(), http_build_query($routeParametersForReferrer));
        }

        $this->includeReferrer = null;
        $this->customPageReferrer = null;

        $this->routeParameters = $currentRouteParameters;
    }
}
