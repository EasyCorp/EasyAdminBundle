<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Router;

use Symfony\Component\Routing\RouteCollection;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminRouteGeneratorInterface
{
    /**
     * This method is called by the custom route loader and must generate all
     * the routes for all the actions of all CRUD controllers and for all dashboards.
     */
    public function generateAll(): RouteCollection;
}
