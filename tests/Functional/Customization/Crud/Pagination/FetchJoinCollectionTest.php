<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Pagination;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Pagination\FetchJoinCollectionTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\DemoEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setPaginatorFetchJoinCollection() configuration method.
 */
class FetchJoinCollectionTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FetchJoinCollectionTestCrudController::class;
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

    public function testFetchJoinCollectionConfiguration(): void
    {
        // one row more than the default page size (20) to get a second page
        DemoEntityFactory::createMany(21);

        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();

        // config affects query performance, not visible output
        $this->assertIndexPageEntityCount(20);
        static::assertIndexFullEntityCount(21);

        static::assertSelectorExists('.list-pagination');
        $this->assertIndexPagesCount(2);
    }
}
