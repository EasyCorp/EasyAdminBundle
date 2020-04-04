<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class UrlBuilder
{
    private $dashboardRoute;
    private $previousPageReferrer;
    private $includeReferrer;
    private $queryParameters;
    private $urlGenerator;

    public function __construct(AdminContext $adminContext, UrlGeneratorInterface $urlGenerator, array $newQueryParameters = [])
    {
        $this->dashboardRoute = $adminContext->getDashboardRouteName();
        $this->urlGenerator = $urlGenerator;

        $previousQueryParameters = $previousQueryParametersCopy = $adminContext->getRequest()->query->all();
        unset($previousQueryParametersCopy['referrer']);
        $previousPageReferrer = sprintf('%s?%s', $adminContext->getRequest()->getPathInfo(), http_build_query($previousQueryParametersCopy));
        $this->previousPageReferrer = $previousPageReferrer;

        $this->queryParameters = array_merge($previousQueryParameters, $newQueryParameters);
    }

    public function setAction(string $action): self
    {
        $this->queryParameters['crudAction'] = $action;

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->queryParameters['entityId'] = $entityId;

        return $this;
    }

    public function setQueryParam(string $paramName, $paramValue): self
    {
        $this->queryParameters[$paramName] = $paramValue;

        return $this;
    }

    public function setQueryParameters(array $queryParameters): self
    {
        $this->queryParameters = $queryParameters;

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
            $this->queryParameters['referrer'] = $this->previousPageReferrer;
        }

        if (false === $this->includeReferrer) {
            unset($this->queryParameters['referrer']);
        }

        ksort($this->queryParameters);

        return $this->urlGenerator->generate($this->dashboardRoute, $this->queryParameters);
    }
}
