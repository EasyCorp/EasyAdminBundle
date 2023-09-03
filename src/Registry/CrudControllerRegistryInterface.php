<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudControllerRegistryInterface
{
    public function findCrudFqcnByEntityFqcn(string $entityFqcn): ?string;

    public function findEntityFqcnByCrudFqcn(string $controllerFqcn): ?string;

    public function findCrudFqcnByCrudId(string $crudId): ?string;

    public function findCrudIdByCrudFqcn(string $controllerFqcn): ?string;
}
