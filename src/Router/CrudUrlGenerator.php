<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudUrlGenerator
{
    private $adminContextProvider;
    private $crudControllers;
    private $urlGenerator;

    public function __construct(AdminContextProvider $adminContextProvider, CrudControllerRegistry $crudControllers, UrlGeneratorInterface $urlGenerator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->crudControllers = $crudControllers;
        $this->urlGenerator = $urlGenerator;
    }

    public function build(array $routeParameters = []): CrudUrlBuilder
    {
        return new CrudUrlBuilder($this->adminContextProvider->getContext(), $this->crudControllers, $this->urlGenerator, $routeParameters);
    }
}
