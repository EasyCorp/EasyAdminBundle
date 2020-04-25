<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudControllerRegistry
{
    private $controllerFqcnToEntityFqcnMap;
    private $entityFqcnToControllerFqcnMap;

    private function __construct()
    {
    }

    /**
     * @param CrudControllerInterface[] $crudControllers
     */
    public static function new(iterable $crudControllers): self
    {
        $registry = new self();

        foreach (iterator_to_array($crudControllers) as $controller) {
            $controllerFqcn = \get_class($controller);
            $registry->controllerFqcnToEntityFqcnMap[$controllerFqcn] = $controller::getEntityFqcn();
        }

        // more than one controller can manage the same entity, so this map will
        // only contain the last controller associated to that repeated entity. That's why
        // several methods in other classes allow to define the CRUD controller explicitly
        $registry->entityFqcnToControllerFqcnMap = array_flip($registry->controllerFqcnToEntityFqcnMap);

        return $registry;
    }

    public function getControllerFqcnByEntityFqcn(string $entityFqcn): ?string
    {
        return $this->entityFqcnToControllerFqcnMap[$entityFqcn] ?? null;
    }

    public function getEntityFqcnByControllerFqcn(string $controllerFqcn): ?string
    {
        return $this->controllerFqcnToEntityFqcnMap[$controllerFqcn] ?? null;
    }
}
