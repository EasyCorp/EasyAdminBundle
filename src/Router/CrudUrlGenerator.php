<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudUrlGenerator
{
    private $adminContextProvider;
    private $urlGenerator;
    private $urlSigner;
    private $dashboardControllerRegistry;
    private $crudControllerRegistry;

    public function __construct(AdminContextProvider $adminContextProvider, UrlGeneratorInterface $urlGenerator, UrlSigner $urlSigner, DashboardControllerRegistry $dashboardControllerRegistry, CrudControllerRegistry $crudControllerRegistry)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->urlGenerator = $urlGenerator;
        $this->urlSigner = $urlSigner;
        $this->dashboardControllerRegistry = $dashboardControllerRegistry;
        $this->crudControllerRegistry = $crudControllerRegistry;
    }

    public function build(array $routeParameters = []): CrudUrlBuilder
    {
        trigger_deprecation('easycorp/easyadmin-bundle', '3.2.0', 'The "%s" class/service is deprecated, use "%s()" instead.', __CLASS__, AdminUrlGenerator::class);

        return new CrudUrlBuilder($this->adminContextProvider->getContext(), $this->urlGenerator, $this->dashboardControllerRegistry, $this->crudControllerRegistry, $this->urlSigner, $routeParameters);
    }
}
