<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudUrlGenerator
{
    private $adminContextProvider;
    private $urlGenerator;

    public function __construct(AdminContextProvider $adminContextProvider, UrlGeneratorInterface $urlGenerator)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->urlGenerator = $urlGenerator;
    }

    public function build(array $routeParameters = []): CrudUrlBuilder
    {
        return new CrudUrlBuilder($this->adminContextProvider->getContext(), $this->urlGenerator, $routeParameters);
    }
}
