<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CrudUrlGenerator
{
    private $applicationContextProvider;
    private $urlGenerator;

    public function __construct(ApplicationContextProvider $applicationContextProvider, UrlGeneratorInterface $urlGenerator)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->urlGenerator = $urlGenerator;
    }

    public function build(array $newQueryParams = []): UrlBuilder
    {
        return new UrlBuilder($this->applicationContextProvider->getContext(), $this->urlGenerator, $newQueryParams);
    }

    public function buildForController(string $controllerFqcn): UrlBuilder
    {
        $applicationContext = $this->applicationContextProvider->getContext();
        $newQueryParams = ['crudController' => $controllerFqcn];

        return (new UrlBuilder($applicationContext, $this->urlGenerator, $newQueryParams))->removeReferrer();
    }
}
