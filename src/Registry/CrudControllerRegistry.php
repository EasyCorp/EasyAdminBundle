<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudControllerRegistry
{
    private $crudFqcnToEntityFqcnMap = [];
    private $entityFqcnToCrudFqcnMap = [];
    private $crudFqcnToCrudIdMap = [];
    private $crudIdToCrudFqcnMap = [];

    /**
     * @param CrudControllerInterface[] $crudControllers
     */
    public function __construct(string $kernelSecret, array $crudControllersFqcn)
    {
        foreach ($crudControllersFqcn as $controllerFqcn) {
            $this->crudFqcnToEntityFqcnMap[$controllerFqcn] = $controllerFqcn::getEntityFqcn();
            $this->crudFqcnToCrudIdMap[$controllerFqcn] = substr(sha1($kernelSecret.$controllerFqcn), 0, 7);
        }

        // more than one controller can manage the same entity, so this map will
        // only contain the last controller associated to that repeated entity. That's why
        // several methods in other classes allow to define the CRUD controller explicitly
        $this->entityFqcnToCrudFqcnMap = array_flip($this->crudFqcnToEntityFqcnMap);
        $this->crudIdToCrudFqcnMap = array_flip($this->crudFqcnToCrudIdMap);
    }

    public function findCrudFqcnByEntityFqcn(string $entityFqcn): ?string
    {
        return $this->entityFqcnToCrudFqcnMap[$entityFqcn] ?? null;
    }

    public function findEntityFqcnByCrudFqcn(string $controllerFqcn): ?string
    {
        return $this->crudFqcnToEntityFqcnMap[$controllerFqcn] ?? null;
    }

    public function findCrudFqcnByCrudId(string $crudId): ?string
    {
        return $this->crudIdToCrudFqcnMap[$crudId] ?? null;
    }

    public function findCrudIdByCrudFqcn(string $controllerFqcn): ?string
    {
        return $this->crudFqcnToCrudIdMap[$controllerFqcn] ?? null;
    }
}
