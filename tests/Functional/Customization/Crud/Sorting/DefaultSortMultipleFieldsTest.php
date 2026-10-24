<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Sorting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Sorting\DefaultSortMultipleFieldsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setDefaultSort() with multiple fields.
 */
class DefaultSortMultipleFieldsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DefaultSortMultipleFieldsTestCrudController::class;
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

    public function testDefaultSortWithMultipleFields(): void
    {
        [$banana1, $apple1, $banana2, $apple2] = CategoryFactory::createSequence([
            ['name' => 'Banana'],
            ['name' => 'Apple'],
            ['name' => 'Banana'],
            ['name' => 'Apple'],
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(4);

        // rows are sorted by name ASC and rows with the same name by id DESC
        $displayedRows = $crawler->filter('tbody tr')->each(static fn ($row) => [
            (int) trim($row->filter('td[data-column="id"]')->text()),
            trim($row->filter('td[data-column="name"]')->text()),
        ]);
        static::assertSame([
            [$apple2->getId(), 'Apple'],
            [$apple1->getId(), 'Apple'],
            [$banana2->getId(), 'Banana'],
            [$banana1->getId(), 'Banana'],
        ], $displayedRows);

        static::assertSelectorExists('.list-pagination');
    }
}
