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

    public function generate(array $newQueryParams): string
    {
        $previousQueryParams = $this->applicationContextProvider->getContext()->getRequest()->query->all();
        unset($previousQueryParams['referrer']);
        $previousQueryParams['referrer'] = $this->doGenerateUrl($previousQueryParams);

        $queryParams = array_merge($previousQueryParams, $newQueryParams);

        return $this->doGenerateUrl($queryParams);
    }

    public function generateWithoutReferrer(array $newQueryParams): string
    {
        $previousQueryParams = $this->applicationContextProvider->getContext()->getRequest()->query->all();
        unset($previousQueryParams['referrer']);

        $queryParams = array_merge($previousQueryParams, $newQueryParams);

        return $this->doGenerateUrl($queryParams);
    }

    private function doGenerateUrl(array $queryParams): string
    {
        $dashboardRoute = $this->applicationContextProvider->getContext()->getDashboard()->getRouteName();
        ksort($queryParams);

        return $this->urlGenerator->generate($dashboardRoute, $queryParams);
    }
}
