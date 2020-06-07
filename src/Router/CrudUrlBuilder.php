<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudUrlBuilder
{
    private $dashboardRoute;
    private $currentPageReferrer;
    private $includeReferrer;
    private $crudControllers;
    private $urlGenerator;
    private $routeParameters;

    public function __construct(AdminContext $adminContext, UrlGeneratorInterface $urlGenerator, array $newRouteParameters = [])
    {
        $this->dashboardRoute = $adminContext->getDashboardRouteName();
        $this->crudControllers = $adminContext->getCrudControllers();
        $this->urlGenerator = $urlGenerator;

        $currentRouteParameters = $currentRouteParametersCopy = $adminContext->getRequest()->query->all();
        unset($currentRouteParametersCopy['referrer']);
        $currentPageReferrer = sprintf('%s?%s', $adminContext->getRequest()->getPathInfo(), http_build_query($currentRouteParametersCopy));
        $this->currentPageReferrer = $currentPageReferrer;

        $this->routeParameters = array_merge($currentRouteParameters, $newRouteParameters);
    }

    public function setCrudId(string $crudId): self
    {
        $this->setRouteParameter('crudId', $crudId);

        return $this;
    }

    public function setController(string $crudControllerFqcn): self
    {
        $this->setRouteParameter('crudControllerFqcn', $crudControllerFqcn);

        return $this;
    }

    public function setAction(string $action): self
    {
        $this->setRouteParameter('crudAction', $action);

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->setRouteParameter('entityId', $entityId);

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

    // this method allows to omit the 'generateUrl()' call in templates, making code more concise
    public function __toString(): string
    {
        return $this->generateUrl();
    }

    public function generateUrl(): string
    {
        if (true === $this->includeReferrer) {
            $this->setRouteParameter('referrer', $this->currentPageReferrer);
        }

        if (false === $this->includeReferrer) {
            $this->unset('referrer');
        }

        // transform 'crudControllerFqcn' into 'crudId'
        if (null !== $crudControllerFqcn = $this->get('crudControllerFqcn')) {
            $this->set('crudId', $this->crudControllers->findCrudIdByCrudFqcn($crudControllerFqcn));
            $this->unset('crudControllerFqcn');
        }

        // this avoids forcing users to always be explicit about the action to execute
        if (null !== $this->get('crudId') && null === $this->get('crudAction')) {
            $this->set('crudAction', Action::INDEX);
        }

        // this removes any parameter with a NULL value
        $routeParameters = array_filter($this->routeParameters, function ($parameterValue) {
            return null !== $parameterValue;
        });
        ksort($routeParameters);

        return $this->urlGenerator->generate($this->dashboardRoute, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
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
