<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pagination\OutputWalkersTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setPaginatorUseOutputWalkers() configuration method.
 */
class OutputWalkersTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return OutputWalkersTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testOutputWalkersConfiguration(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // config affects query performance, not visible output
        // verify page loads correctly with data
        $this->assertIndexPageEntityCount(20);

        // verify total count
        static::assertIndexFullEntityCount(100);

        // verify pagination still works
        static::assertSelectorExists('.list-pagination');
        $this->assertIndexPagesCount(5);
    }
}
