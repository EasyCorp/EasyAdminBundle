<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Sorting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Sorting\DefaultSortTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\DemoEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setDefaultSort() configuration method.
 */
class DefaultSortTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DefaultSortTestCrudController::class;
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

    public function testDefaultSortConfiguration(): void
    {
        $entities = DemoEntityFactory::createMany(3);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(3);

        $displayedIds = $crawler->filter('tbody tr td[data-column="id"]')->each(static fn ($cell) => (int) trim($cell->text()));
        $expectedIds = array_reverse(array_map(static fn ($entity) => $entity->getId(), $entities));
        static::assertSame($expectedIds, $displayedIds, 'Rows should be sorted by id DESC');

        static::assertSelectorExists('.list-pagination');
    }
}
