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
    private $dashboardControllerRegistry;
    private $crudControllerRegistry;

    public function __construct(AdminContextProvider $adminContextProvider, UrlGeneratorInterface $urlGenerator, DashboardControllerRegistry $dashboardControllerRegistry, CrudControllerRegistry $crudControllerRegistry)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->urlGenerator = $urlGenerator;
        $this->dashboardControllerRegistry = $dashboardControllerRegistry;
        $this->crudControllerRegistry = $crudControllerRegistry;
    }

    public function build(array $routeParameters = []): CrudUrlBuilder
    {
        return new CrudUrlBuilder($this->adminContextProvider->getContext(), $this->urlGenerator, $this->dashboardControllerRegistry, $this->crudControllerRegistry, $routeParameters);
    }
}
