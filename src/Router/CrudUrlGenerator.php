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
