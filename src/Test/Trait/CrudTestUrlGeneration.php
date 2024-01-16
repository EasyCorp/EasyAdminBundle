<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\Exception\InvalidClassPropertyTypeException;
use EasyCorp\Bundle\EasyAdminBundle\Test\Exception\MissingClassMethodException;

trait CrudTestUrlGeneration
{
    /**
     * @param array<string, string> $options
     *
     * @throws InvalidClassPropertyTypeException
     * @throws MissingClassMethodException
     */
    protected function getCrudUrl(string $action, string|int|null $entityId = null, array $options = [], ?string $dashboardFqcn = null, ?string $controllerFqcn = null): string
    {
        $dashboardFqcn ??= $this->getDashboardFqcn();
        $controllerFqcn ??= $this->getControllerFqcn();

        $this->adminUrlGenerator
            ->setDashboard($dashboardFqcn)
            ->setController($controllerFqcn)
            ->setAction($action)
        ;

        if (null !== $entityId) {
            $this->adminUrlGenerator->setEntityId($entityId);
        }

        foreach ($options as $key => $value) {
            $this->adminUrlGenerator->set($key, $value);
        }

        return $this->adminUrlGenerator->generateUrl();
    }

    protected function generateIndexUrl(?string $query = null, ?string $dashboardFqcn = null, ?string $controllerFqcn = null): string
    {
        $options = [];

        if (null !== $query) {
            $options['query'] = $query;
        }

        return $this->getCrudUrl(Action::INDEX, null, $options, $dashboardFqcn, $controllerFqcn);
    }

    protected function generateNewFormUrl(?string $dashboardFqcn = null, ?string $controllerFqcn = null): string
    {
        return $this->getCrudUrl(Action::NEW, dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
    }

    protected function generateEditFormUrl(string|int $id, ?string $dashboardFqcn = null, ?string $controllerFqcn = null): string
    {
        return $this->getCrudUrl(Action::EDIT, $id, dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
    }

    protected function generateDetailUrl(string|int $id, ?string $dashboardFqcn = null, ?string $controllerFqcn = null): string
    {
        return $this->getCrudUrl(Action::DETAIL, $id, dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
    }

    protected function generateFilterRenderUrl(?string $dashboardFqcn = null, ?string $controllerFqcn = null): string
    {
        return $this->getCrudUrl('renderFilters', dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
    }
}
