<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DashboardControllerRegistry
{
    private $controllerFqcnToContextIdMap = [];
    private $contextIdToControllerFqcnMap = [];

    public function __construct(string $kernelSecret, iterable $dashboardControllers)
    {
        foreach (iterator_to_array($dashboardControllers, false) as $controller) {
            $controllerFqcn = \get_class($controller);
            $this->controllerFqcnToContextIdMap[$controllerFqcn] = substr(sha1($kernelSecret.$controllerFqcn), 0, 7);
        }

        $this->contextIdToControllerFqcnMap = array_flip($this->controllerFqcnToContextIdMap);
    }

    public function getControllerFqcnByContextId(string $contextId): ?string
    {
        return $this->contextIdToControllerFqcnMap[$contextId] ?? null;
    }

    public function getContextIdByControllerFqcn(string $controllerFqcn): ?string
    {
        return $this->controllerFqcnToContextIdMap[$controllerFqcn] ?? null;
    }
}
