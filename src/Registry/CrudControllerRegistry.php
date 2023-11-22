<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudControllerRegistry
{
    private array $crudFqcnToEntityFqcnMap;
    private array $entityFqcnToCrudFqcnMap;
    private array $crudFqcnToCrudIdMap;
    private array $crudIdToCrudFqcnMap;

    /**
     * @param array<string, string> $crudFqcnToEntityFqcnMap
     * @param array<string, string> $crudFqcnToCrudIdMap
     * @param array<string, string> $crudIdToCrudFqcnMap
     * @param array<string, string> $entityFqcnToCrudFqcnMap
     */
    public function __construct(array $crudFqcnToEntityFqcnMap, array $crudFqcnToCrudIdMap, array $entityFqcnToCrudFqcnMap, array $crudIdToCrudFqcnMap)
    {
        $this->crudFqcnToEntityFqcnMap = $crudFqcnToEntityFqcnMap;
        $this->crudFqcnToCrudIdMap = $crudFqcnToCrudIdMap;
        $this->entityFqcnToCrudFqcnMap = $entityFqcnToCrudFqcnMap;
        $this->crudIdToCrudFqcnMap = $crudIdToCrudFqcnMap;
    }

    public function findCrudFqcnByEntityFqcn(string $entityFqcn): ?string
    {
        return $this->entityFqcnToCrudFqcnMap[$entityFqcn] ?? null;
    }

    public function findEntityFqcnByCrud(CrudControllerInterface $controllerFqcn): string
    {
        return $this->crudFqcnToEntityFqcnMap[$controllerFqcn::class] ?? $controllerFqcn::getEntityFqcn();
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
