<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

/**
 * @deprecated since 4.28.1, use AdminControllerRegistry instead
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudControllerRegistry
{
    /**
     * @param array<class-string, class-string> $crudFqcnToEntityFqcnMap
     * @param array<class-string, string>       $crudFqcnToCrudIdMap
     * @param array<class-string, class-string> $entityFqcnToCrudFqcnMap
     * @param array<string, class-string>       $crudIdToCrudFqcnMap
     */
    public function __construct(
        private readonly array $crudFqcnToEntityFqcnMap,
        private readonly array $crudFqcnToCrudIdMap,
        private readonly array $entityFqcnToCrudFqcnMap,
        private readonly array $crudIdToCrudFqcnMap,
    ) {
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::findCrudControllerByEntity() instead
     *
     * @param class-string $entityFqcn
     *
     * @return class-string|null
     */
    public function findCrudFqcnByEntityFqcn(string $entityFqcn): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::findCrudControllerByEntity()" instead.',
            __METHOD__
        );

        return $this->entityFqcnToCrudFqcnMap[$entityFqcn] ?? null;
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::findEntityByCrudController() instead
     *
     * @param class-string $controllerFqcn
     *
     * @return class-string|null
     */
    public function findEntityFqcnByCrudFqcn(string $controllerFqcn): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::findEntityByCrudController()" instead.',
            __METHOD__
        );

        return $this->crudFqcnToEntityFqcnMap[$controllerFqcn] ?? null;
    }

    /**
     * @deprecated since 4.28.1, this concept (crudId) no longer exists in modern EasyAdmin
     *
     * @return class-string|null
     */
    public function findCrudFqcnByCrudId(string $crudId): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated and will be removed in EasyAdmin 5.x. The "crudId" concept no longer exists in modern EasyAdmin with pretty URLs.',
            __METHOD__
        );

        return $this->crudIdToCrudFqcnMap[$crudId] ?? null;
    }

    /**
     * @deprecated since 4.28.1, this concept (crudId) no longer exists in modern EasyAdmin
     *
     * @param class-string $controllerFqcn
     */
    public function findCrudIdByCrudFqcn(string $controllerFqcn): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated and will be removed in EasyAdmin 5.x. The "crudId" concept no longer exists in modern EasyAdmin with pretty URLs.',
            __METHOD__
        );

        return $this->crudFqcnToCrudIdMap[$controllerFqcn] ?? null;
    }

    /**
     * @deprecated since 4.28.1, use AdminControllerRegistry::getAllCrudControllers() instead
     *
     * @return array<int, class-string>
     */
    public function getAll(): array
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.28.1',
            'The "%s()" method is deprecated. Use "AdminControllerRegistry::getAllCrudControllers()" instead.',
            __METHOD__
        );

        return array_values($this->entityFqcnToCrudFqcnMap);
    }
}
