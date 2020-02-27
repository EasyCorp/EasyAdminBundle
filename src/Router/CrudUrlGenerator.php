<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CrudUrlGenerator
{
    private $applicationContextProvider;
    private $urlGenerator;
    private $queryParams = [];
    private $includeReferrer;

    public function __construct(ApplicationContextProvider $applicationContextProvider, UrlGeneratorInterface $urlGenerator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->urlGenerator = $urlGenerator;
    }

    public function generateForController(string $controllerFqcn): self
    {
        $generator = new self($this->applicationContextProvider, $this->urlGenerator);
        $generator->includeReferrer = false;
        $generator->queryParams['crudController'] = $controllerFqcn;

        return $generator;
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

    public function getUrl(): string
    {
        if (null === $this->includeReferrer()) {
            return $this->generate($this->queryParams);
        }

        return $this->includeReferrer
            ? $this->generateCurrentUrlWithReferrer($this->queryParams)
            : $this->generateCurrentUrlWithoutReferrer($this->queryParams);
    }



    public function generate(array $queryParams = []): string
    {
        return $this->doGenerateUrl($queryParams);
    }

    public function generateCurrentUrl(array $updatedQueryParams = []): string
    {
        $previousQueryParams = $this->applicationContextProvider->getContext()->getRequest()->query->all();
        $newQueryParams = array_merge($previousQueryParams, $updatedQueryParams);

        return $this->doGenerateUrl($newQueryParams);
    }

    public function generateCurrentUrlWithReferrer(array $updatedQueryParams = []): string
    {
        $request = $this->applicationContextProvider->getContext()->getRequest();
        $previousQueryParams = $request->query->all();
        unset($previousQueryParams['referrer']);
        $newReferrer = sprintf('%s?%s', $request->getPathInfo(), http_build_query($previousQueryParams));

        $newQueryParams = array_merge($previousQueryParams, ['referrer' => $newReferrer], $updatedQueryParams);

        return $this->doGenerateUrl($newQueryParams);
    }

    public function generateCurrentUrlWithoutReferrer(array $updatedQueryParams = []): string
    {
        $previousQueryParams = $this->applicationContextProvider->getContext()->getRequest()->query->all();
        unset($previousQueryParams['referrer']);

        $newQueryParams = array_merge($previousQueryParams, $updatedQueryParams);

        return $this->doGenerateUrl($newQueryParams);
    }

    private function doGenerateUrl(array $queryParams): string
    {
        $dashboardRoute = $this->applicationContextProvider->getContext()->getDashboardRouteName();
        ksort($queryParams);

        return $this->urlGenerator->generate($dashboardRoute, $queryParams);
    }
}
