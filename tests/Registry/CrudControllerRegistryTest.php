<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use PHPUnit\Framework\TestCase;

class CrudControllerRegistryTest extends TestCase
{
    private CrudControllerRegistry $registry;

    protected function setUp(): void
    {
        $crudFqcnToEntityFqcnMap = [
            'App\Controller\ProductCrudController' => 'App\Entity\Product',
            'App\Controller\CategoryCrudController' => 'App\Entity\Category',
            'App\Controller\UserCrudController' => 'App\Entity\User',
        ];

        $crudFqcnToCrudIdMap = [
            'App\Controller\ProductCrudController' => 'product',
            'App\Controller\CategoryCrudController' => 'category',
            'App\Controller\UserCrudController' => 'user',
        ];

        $entityFqcnToCrudFqcnMap = [
            'App\Entity\Product' => 'App\Controller\ProductCrudController',
            'App\Entity\Category' => 'App\Controller\CategoryCrudController',
            'App\Entity\User' => 'App\Controller\UserCrudController',
        ];

        $crudIdToCrudFqcnMap = [
            'product' => 'App\Controller\ProductCrudController',
            'category' => 'App\Controller\CategoryCrudController',
            'user' => 'App\Controller\UserCrudController',
        ];

        $this->registry = new CrudControllerRegistry(
            $crudFqcnToEntityFqcnMap,
            $crudFqcnToCrudIdMap,
            $entityFqcnToCrudFqcnMap,
            $crudIdToCrudFqcnMap
        );
    }

    public function testFindCrudFqcnByEntityFqcnReturnsControllerWhenEntityExists(): void
    {
        $result = $this->registry->findCrudFqcnByEntityFqcn('App\Entity\Product');

        $this->assertSame('App\Controller\ProductCrudController', $result);
    }

    public function testFindCrudFqcnByEntityFqcnReturnsNullWhenEntityDoesNotExist(): void
    {
        $result = $this->registry->findCrudFqcnByEntityFqcn('App\Entity\NonExistent');

        $this->assertNull($result);
    }

    public function testFindEntityFqcnByCrudFqcnReturnsEntityWhenControllerExists(): void
    {
        $result = $this->registry->findEntityFqcnByCrudFqcn('App\Controller\CategoryCrudController');

        $this->assertSame('App\Entity\Category', $result);
    }

    public function testFindEntityFqcnByCrudFqcnReturnsNullWhenControllerDoesNotExist(): void
    {
        $result = $this->registry->findEntityFqcnByCrudFqcn('App\Controller\NonExistentCrudController');

        $this->assertNull($result);
    }

    public function testFindCrudFqcnByCrudIdReturnsControllerWhenIdExists(): void
    {
        $result = $this->registry->findCrudFqcnByCrudId('user');

        $this->assertSame('App\Controller\UserCrudController', $result);
    }

    public function testFindCrudFqcnByCrudIdReturnsNullWhenIdDoesNotExist(): void
    {
        $result = $this->registry->findCrudFqcnByCrudId('nonexistent');

        $this->assertNull($result);
    }

    public function testFindCrudIdByCrudFqcnReturnsIdWhenControllerExists(): void
    {
        $result = $this->registry->findCrudIdByCrudFqcn('App\Controller\ProductCrudController');

        $this->assertSame('product', $result);
    }

    public function testFindCrudIdByCrudFqcnReturnsNullWhenControllerDoesNotExist(): void
    {
        $result = $this->registry->findCrudIdByCrudFqcn('App\Controller\NonExistentCrudController');

        $this->assertNull($result);
    }

    public function testGetAllReturnsAllCrudControllers(): void
    {
        $result = $this->registry->getAll();

        $this->assertCount(3, $result);
        $this->assertContains('App\Controller\ProductCrudController', $result);
        $this->assertContains('App\Controller\CategoryCrudController', $result);
        $this->assertContains('App\Controller\UserCrudController', $result);
    }

    public function testGetAllReturnsEmptyArrayWhenNoControllersRegistered(): void
    {
        $emptyRegistry = new CrudControllerRegistry([], [], [], []);

        $result = $emptyRegistry->getAll();

        $this->assertSame([], $result);
    }

    public function testRegistryWithEmptyMaps(): void
    {
        $emptyRegistry = new CrudControllerRegistry([], [], [], []);

        $this->assertNull($emptyRegistry->findCrudFqcnByEntityFqcn('App\Entity\Product'));
        $this->assertNull($emptyRegistry->findEntityFqcnByCrudFqcn('App\Controller\ProductCrudController'));
        $this->assertNull($emptyRegistry->findCrudFqcnByCrudId('product'));
        $this->assertNull($emptyRegistry->findCrudIdByCrudFqcn('App\Controller\ProductCrudController'));
    }

    public function testBidirectionalMapping(): void
    {
        $entityFqcn = 'App\Entity\Category';
        $crudFqcn = $this->registry->findCrudFqcnByEntityFqcn($entityFqcn);

        $this->assertNotNull($crudFqcn);

        $resolvedEntityFqcn = $this->registry->findEntityFqcnByCrudFqcn($crudFqcn);
        $this->assertSame($entityFqcn, $resolvedEntityFqcn);
    }

    public function testCrudIdBidirectionalMapping(): void
    {
        $crudId = 'product';
        $crudFqcn = $this->registry->findCrudFqcnByCrudId($crudId);

        $this->assertNotNull($crudFqcn);

        $resolvedCrudId = $this->registry->findCrudIdByCrudFqcn($crudFqcn);
        $this->assertSame($crudId, $resolvedCrudId);
    }
}
