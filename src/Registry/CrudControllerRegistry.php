<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudControllerRegistry
{
    /** @var array<class-string, class-string> */
    private array $crudFqcnToEntityFqcnMap;
    /** @var array<class-string, class-string> */
    private array $entityFqcnToCrudFqcnMap;
    /** @var array<class-string, string> */
    private array $crudFqcnToCrudIdMap;
    /** @var array<string, class-string> */
    private array $crudIdToCrudFqcnMap;

    /**
     * @param array<class-string, class-string> $crudFqcnToEntityFqcnMap
     * @param array<class-string, string>       $crudFqcnToCrudIdMap
     * @param array<string, class-string>       $crudIdToCrudFqcnMap
     * @param array<class-string, class-string> $entityFqcnToCrudFqcnMap
     */
    public function __construct(array $crudFqcnToEntityFqcnMap, array $crudFqcnToCrudIdMap, array $entityFqcnToCrudFqcnMap, array $crudIdToCrudFqcnMap)
    {
        $this->crudFqcnToEntityFqcnMap = $crudFqcnToEntityFqcnMap;
        $this->crudFqcnToCrudIdMap = $crudFqcnToCrudIdMap;
        $this->entityFqcnToCrudFqcnMap = $entityFqcnToCrudFqcnMap;
        $this->crudIdToCrudFqcnMap = $crudIdToCrudFqcnMap;
    }

    /**
     * @param class-string $entityFqcn
     *
     * @return class-string|null
     */
    public function findCrudFqcnByEntityFqcn(string $entityFqcn): ?string
    {
        return $this->entityFqcnToCrudFqcnMap[$entityFqcn] ?? null;
    }

    /**
     * @param class-string $controllerFqcn
     *
     * @return class-string|null
     */
    public function findEntityFqcnByCrudFqcn(string $controllerFqcn): ?string
    {
        return $this->crudFqcnToEntityFqcnMap[$controllerFqcn] ?? null;
    }

    /**
     * @return class-string|null
     */
    public function findCrudFqcnByCrudId(string $crudId): ?string
    {
        return $this->crudIdToCrudFqcnMap[$crudId] ?? null;
    }

    /**
     * @param class-string $controllerFqcn
     */
    public function findCrudIdByCrudFqcn(string $controllerFqcn): ?string
    {
        return $this->crudFqcnToCrudIdMap[$controllerFqcn] ?? null;
    }

    /**
     * @return array<int, class-string>
     */
    public function getAll(): array
    {
        return array_values($this->entityFqcnToCrudFqcnMap);
    }
}
