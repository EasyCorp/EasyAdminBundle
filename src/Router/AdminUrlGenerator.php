<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AdminUrlGenerator
{
    private $isInitialized;
    private $adminContextProvider;
    private $urlGenerator;
    private $dashboardControllerRegistry;
    private $crudControllerRegistry;
    private $urlSigner;
    private $dashboardRoute;
    private $includeReferrer;
    private $addSignature;
    private $routeParameters;
    private $currentPageReferrer;

    public function __construct(AdminContextProvider $adminContextProvider, UrlGeneratorInterface $urlGenerator, DashboardControllerRegistry $dashboardControllerRegistry, CrudControllerRegistry $crudControllerRegistry, UrlSigner $urlSigner)
    {
        $this->isInitialized = false;
        $this->adminContextProvider = $adminContextProvider;
        $this->urlGenerator = $urlGenerator;
        $this->dashboardControllerRegistry = $dashboardControllerRegistry;
        $this->crudControllerRegistry = $crudControllerRegistry;
        $this->urlSigner = $urlSigner;
    }

    public function setDashboard(string $dashboardControllerFqcn): self
    {
        $this->setRouteParameter(EA::DASHBOARD_CONTROLLER_FQCN, $dashboardControllerFqcn);

        return $this;
    }

    public function setCrudId(string $crudId): self
    {
        $crudControllerFqcn = $this->crudControllerRegistry->findCrudFqcnByCrudId($crudId);
        trigger_deprecation('easycorp/easyadmin-bundle', '3.2.0', 'The "setCrudId()" method of the "%s" service and the related "%s" query parameter are deprecated. Instead, use the CRUD Controller FQCN and the "setController()" method like this: ->setController(\'%s\').', __CLASS__, EA::CRUD_ID, str_replace('\\', '\\\\', $crudControllerFqcn));

        $this->setRouteParameter(EA::CRUD_CONTROLLER_FQCN, $crudControllerFqcn);

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
        $this->unsetAllExcept(EA::MENU_INDEX, EA::SUBMENU_INDEX, EA::DASHBOARD_CONTROLLER_FQCN);
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

    public function addSignature(bool $addSignature = true): self
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        $this->addSignature = $addSignature;

        return $this;
    }

    public function getSignature(): string
    {
        if (false === $this->isInitialized) {
            $this->initialize();
        }

        $this->addSignature = true;
        $url = $this->generateUrl();
        $urlParts = parse_url($url);
        $queryString = $urlParts['query'];
        parse_str($queryString, $queryParts);

        return $queryParts['signature'];
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
            $this->setRouteParameter(EA::REFERRER, $this->currentPageReferrer);
        }

        if (false === $this->includeReferrer) {
            $this->unset(EA::REFERRER);
        }

        // transform 'crudId' into 'crudControllerFqcn'
        if (null !== $crudId = $this->get(EA::CRUD_ID)) {
            if (null === $crudControllerFqcn = $this->crudControllerRegistry->findCrudFqcnByCrudId($crudId)) {
                throw new \InvalidArgumentException(sprintf('The given "%s" value is not a valid CRUD ID. Instead of dealing with CRUD controller IDs when generating admin URLs, use the "setController()" method to set the CRUD controller FQCN.', $crudId));
            }

            $this->set(EA::CRUD_CONTROLLER_FQCN, $crudControllerFqcn);
            $this->unset(EA::CRUD_ID);
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

        // needed for i18n routes, whose name follows the pattern "route_name.locale"
        $this->dashboardRoute = explode('.', $this->dashboardRoute, 2)[0];

        // this removes any parameter with a NULL value
        $routeParameters = array_filter($this->routeParameters, static function ($parameterValue) {
            return null !== $parameterValue;
        });
        ksort($routeParameters, \SORT_STRING);

        $context = $this->adminContextProvider->getContext();
        $urlType = null !== $context && false === $context->getAbsoluteUrls() ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_URL;
        $url = $this->urlGenerator->generate($this->dashboardRoute, $routeParameters, $urlType);

        if ($this->signUrls()) {
            $url = $this->urlSigner->sign($url);
        }

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
        $this->addSignature = null;

        $this->routeParameters = $currentRouteParameters;
    }

    private function signUrls(): bool
    {
        if (null !== $this->addSignature) {
            return $this->addSignature;
        }

        if (null !== $adminContext = $this->adminContextProvider->getContext()) {
            return $adminContext->getSignedUrls();
        }

        return true;
    }
}
