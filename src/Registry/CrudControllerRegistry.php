<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudControllerRegistry
{
    /**
     * @param array<class-string, class-string> $crudFqcnToEntityFqcnMap
     * @param array<class-string, string>       $crudFqcnToCrudIdMap
     * @param array<string, class-string>       $crudIdToCrudFqcnMap
     * @param array<class-string, class-string> $entityFqcnToCrudFqcnMap
     */
    public function __construct(
        private readonly array $crudFqcnToEntityFqcnMap,
        private readonly array $crudFqcnToCrudIdMap,
        private readonly array $entityFqcnToCrudFqcnMap,
        private readonly array $crudIdToCrudFqcnMap,
    ) {
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
