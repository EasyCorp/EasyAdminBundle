<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UrlBuilder
{
    private $dashboardRoute;
    private $previousPageReferrer;
    private $includeReferrer;
    private $queryParams;
    private $urlGenerator;

    public function __construct(ApplicationContext $applicationContext, UrlGeneratorInterface $urlGenerator, array $newQueryParams = [])
    {
        $this->dashboardRoute = $applicationContext->getDashboardRouteName();
        $this->urlGenerator = $urlGenerator;

        $previousQueryParams = $previousQueryParamsCopy = $applicationContext->getRequest()->query->all();
        unset($previousQueryParamsCopy['referrer']);
        $previousPageReferrer = sprintf('%s?%s', $applicationContext->getRequest()->getPathInfo(), http_build_query($previousQueryParamsCopy));
        $this->previousPageReferrer = $previousPageReferrer;

        $this->queryParams = array_merge($previousQueryParams, $newQueryParams);
    }

    public function setAction(string $action): self
    {
        $this->queryParams['crudAction'] = $action;

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->queryParams['entityId'] = $entityId;

        return $this;
    }

    public function setQueryParam(string $paramName, $paramValue): self
    {
        $this->queryParams[$paramName] = $paramValue;

        return $this;
    }

    public function setQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;

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

    public function generateUrl(): string
    {
        if (true === $this->includeReferrer) {
            $this->queryParams['referrer'] = $this->previousPageReferrer;
        }

        if (false === $this->includeReferrer) {
            unset($this->queryParams['referrer']);
        }

        ksort($this->queryParams);

        return $this->urlGenerator->generate($this->dashboardRoute, $this->queryParams);
    }
}
