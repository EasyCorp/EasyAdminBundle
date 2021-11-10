<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudUrlBuilder
{
    private $dashboardRoute;
    private $includeReferrer;
    private $dashboardControllers;
    private $crudControllers;
    private $urlGenerator;
    private $urlSigner;
    private $routeParameters;
    private $signUrls;
    private $currentPageReferrer;

    public function __construct(?AdminContext $adminContext, UrlGeneratorInterface $urlGenerator, DashboardControllerRegistry $dashboardControllers, CrudControllerRegistry $crudControllers, UrlSigner $urlSigner, array $newRouteParameters = [])
    {
        $this->dashboardRoute = null === $adminContext ? null : $adminContext->getDashboardRouteName();
        $this->dashboardControllers = $dashboardControllers;
        $this->crudControllers = $crudControllers;
        $this->urlGenerator = $urlGenerator;
        $this->urlSigner = $urlSigner;

        $this->signUrls = null === $adminContext ? false : $adminContext->getSignedUrls();
        $currentRouteParameters = $currentRouteParametersCopy = null === $adminContext ? [] : $adminContext->getRequest()->query->all();
        unset($currentRouteParametersCopy[EA::REFERRER]);
        $currentPageReferrer = null === $adminContext ? null : sprintf('%s?%s', $adminContext->getRequest()->getPathInfo(), http_build_query($currentRouteParametersCopy));
        $this->currentPageReferrer = $currentPageReferrer;

        $this->routeParameters = array_merge($currentRouteParameters, $newRouteParameters);
    }

    public function setDashboard(string $dashboardControllerFqcn): self
    {
        $this->setRouteParameter('dashboardControllerFqcn', $dashboardControllerFqcn);

        return $this;
    }

    public function setCrudId(string $crudId): self
    {
        $this->setRouteParameter(EA::CRUD_ID, $crudId);

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

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->setRouteParameter(EA::ENTITY_ID, $entityId);

        return $this;
    }

    public function get(string $paramName)
    {
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
        unset($this->routeParameters[$paramName]);

        return $this;
    }

    public function unsetAll(): self
    {
        $this->routeParameters = [];

        return $this;
    }

    public function includeReferrer(): self
    {
        $this->includeReferrer = true;

        return $this;
    }

    public function removeReferrer(): self
    {
        $this->includeReferrer = false;

        return $this;
    }

    public function addSignature(bool $addSignature = true): self
    {
        $this->signUrls = $addSignature;

        return $this;
    }

    // this method allows to omit the 'generateUrl()' call in templates, making code more concise
    public function __toString(): string
    {
        return $this->generateUrl();
    }

    public function generateUrl(): string
    {
        trigger_deprecation('easycorp/easyadmin-bundle', '3.2.0', 'The "%s" class/service is deprecated, use "%s()" instead.', __CLASS__, AdminUrlGenerator::class);

        if (true === $this->includeReferrer) {
            $this->setRouteParameter(EA::REFERRER, $this->currentPageReferrer);
        }

        if (false === $this->includeReferrer) {
            $this->unset(EA::REFERRER);
        }

        // transform 'crudControllerFqcn' into 'crudId'
        if (null !== $crudControllerFqcn = $this->get(EA::CRUD_CONTROLLER_FQCN)) {
            if (null === $crudId = $this->crudControllers->findCrudIdByCrudFqcn($crudControllerFqcn)) {
                throw new \InvalidArgumentException(sprintf('The given "%s" class is not a valid CRUD controller. Make sure it extends from "%s" or implements "%s".', $crudControllerFqcn, AbstractCrudController::class, CrudControllerInterface::class));
            }

            $this->set(EA::CRUD_ID, $crudId);
            $this->unset(EA::CRUD_CONTROLLER_FQCN);
        }

        // this avoids forcing users to always be explicit about the action to execute
        if (null !== $this->get(EA::CRUD_ID) && null === $this->get(EA::CRUD_ACTION)) {
            $this->set(EA::CRUD_ACTION, Action::INDEX);
        }

        // if the Dashboard FQCN is defined, find its route and use it to override
        // the current route (this is needed to allow generating links to different dashboards)
        if (null !== $dashboardControllerFqcn = $this->get(EA::DASHBOARD_CONTROLLER_FQCN)) {
            if (null === $dashboardRoute = $this->dashboardControllers->getRouteByControllerFqcn($dashboardControllerFqcn)) {
                throw new \InvalidArgumentException(sprintf('The given "%s" class is not a valid Dashboard controller. Make sure it extends from "%s" or implements "%s".', $dashboardControllerFqcn, AbstractDashboardController::class, DashboardControllerInterface::class));
            }

            $this->dashboardRoute = $dashboardRoute;
            $this->unset(EA::DASHBOARD_CONTROLLER_FQCN);
        }

        // this happens when generating URLs from outside EasyAdmin (AdminContext is null) and
        // no Dashboard FQCn has been defined explicitly
        if (null === $this->dashboardRoute) {
            if ($this->dashboardControllers->getNumberOfDashboards() > 1) {
                throw new \RuntimeException('When generating CRUD URLs from outside EasyAdmin, if your application has more than one Dashboard, you must associate the URL to a specific Dashboard using the "setDashboard()" method.');
            }

            $this->dashboardRoute = $this->dashboardControllers->getFirstDashboardRoute();
        }

        // needed for i18n routes, whose name follows the pattern "route_name.locale"
        $this->dashboardRoute = explode('.', $this->dashboardRoute, 2)[0];

        // this removes any parameter with a NULL value
        $routeParameters = array_filter($this->routeParameters, static function ($parameterValue) {
            return null !== $parameterValue;
        });
        ksort($routeParameters, \SORT_STRING);

        $url = $this->urlGenerator->generate($this->dashboardRoute, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);

        if ($this->signUrls) {
            $url = $this->urlSigner->sign($url);
        }

        return $url;
    }

    private function setRouteParameter(string $paramName, $paramValue): void
    {
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
}
