<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityConfig;
use EasyCorp\Bundle\EasyAdminBundle\Dashboard\DashboardInterface;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A context object that stores all the config about the current dashboard and resource.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ApplicationContext
{
    public const ATTRIBUTE_KEY = 'easyadmin_context';

    private $request;
    private $dashboard;
    private $menu;
    private $entity;
    private $entityConfig;

    public function __construct(Request $request, DashboardInterface $dashboard, MenuProviderInterface $menu, ?EntityConfig $entityConfig, $entity)
    {
        $this->request = $request;
        $this->dashboard = $dashboard;
        $this->menu = $menu;
        $this->entityConfig = $entityConfig;
        $this->entity = $entity;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getDashboard(): DashboardInterface
    {
        return $this->dashboard;
    }

    public function getMenu(): MenuProviderInterface
    {
        return $this->menu;
    }

    /**
     * @return object|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getEntityConfig(): ?EntityConfig
    {
        return $this->entityConfig;
    }

    public function getDashboardRouteName(): string
    {
        return $this->request->attributes->get('_route');
    }
}
