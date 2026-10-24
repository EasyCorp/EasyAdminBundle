<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionClickTriggerDoubleCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionDetailCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionDisabledCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionFallbackCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionMissingCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;

class DefaultRowActionCrudControllerTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return DefaultRowActionCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
    }

    public function testDefaultRowActionWithEditAction(): void
    {
        $category = CategoryFactory::createOne();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // by default, the default row action is Action::EDIT
        // check that rows have the data-default-action-url attribute and the class
        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertCount(1, $rows);

        $firstRow = $rows->first();
        static::assertSame((string) $category->getId(), $firstRow->attr('data-id'));
        static::assertNotNull($firstRow->attr('data-default-action-url'), 'Row should have data-default-action-url attribute');
        static::assertStringContainsString(sprintf('/%d/edit', $category->getId()), $firstRow->attr('data-default-action-url'), 'URL should contain edit action');

        $table = $crawler->filter('table.datagrid');
        static::assertEquals('single', $table->attr('data-default-action-trigger'), 'Click trigger should be single by default');
    }

    public function testDefaultRowActionWithDetailAction(): void
    {
        $category = CategoryFactory::createOne();

        // use a different controller that sets Action::DETAIL as default
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionDetailCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertCount(1, $rows);

        $firstRow = $rows->first();
        static::assertSame((string) $category->getId(), $firstRow->attr('data-id'));
        static::assertNotNull($firstRow->attr('data-default-action-url'), 'Row should have data-default-action-url attribute');
        static::assertStringContainsString('/'.$category->getId(), $firstRow->attr('data-default-action-url'), 'URL should contain the entity ID for detail action');
        static::assertStringNotContainsString('/edit', $firstRow->attr('data-default-action-url'));
    }

    public function testDefaultRowActionDisabled(): void
    {
        $category = CategoryFactory::createOne();

        // use a different controller that disables the default row action
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionDisabledCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertCount(1, $rows);

        $firstRow = $rows->first();
        static::assertSame((string) $category->getId(), $firstRow->attr('data-id'));
        static::assertNull($firstRow->attr('data-default-action-url'), 'Row should NOT have data-default-action-url attribute when disabled');
        static::assertStringNotContainsString('ea-clickable-row', $firstRow->attr('class') ?? '', 'Row should NOT have ea-clickable-row class when disabled');
    }

    public function testDefaultRowActionWithMissingAction(): void
    {
        $category = CategoryFactory::createOne();

        // use a controller where the edit action is disabled and detail is not added
        // the default row action fallback is [EDIT, DETAIL] but neither are available
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionMissingCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertCount(1, $rows);

        $firstRow = $rows->first();
        static::assertSame((string) $category->getId(), $firstRow->attr('data-id'));
        static::assertNull($firstRow->attr('data-default-action-url'), 'Row should NOT have data-default-action-url when no fallback action is available');
        static::assertStringNotContainsString('ea-clickable-row', $firstRow->attr('class') ?? '', 'Row should NOT have ea-clickable-row class when no fallback action is available');
    }

    public function testDefaultRowActionFallbackToDetail(): void
    {
        $category = CategoryFactory::createOne();

        // use a controller where EDIT is disabled but DETAIL is enabled
        // the default row action should fallback from EDIT to DETAIL
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionFallbackCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertCount(1, $rows);

        $firstRow = $rows->first();
        static::assertSame((string) $category->getId(), $firstRow->attr('data-id'));
        static::assertNotNull($firstRow->attr('data-default-action-url'), 'Row should have data-default-action-url with fallback to DETAIL');
        static::assertStringContainsString('/'.$category->getId(), $firstRow->attr('data-default-action-url'), 'URL should contain the entity ID for detail action (fallback)');
        static::assertStringNotContainsString('/edit', $firstRow->attr('data-default-action-url'));
    }

    public function testDefaultRowActionUrlIsCorrectForEachEntity(): void
    {
        $categories = CategoryFactory::createMany(2);
        $expectedIds = array_map(static fn ($category): string => (string) $category->getId(), $categories);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertCount(2, $rows);

        $entityIds = $rows->each(static function ($row): string {
            $entityId = (string) $row->attr('data-id');
            $url = $row->attr('data-default-action-url');

            static::assertNotNull($url, 'Each row should have a data-default-action-url');
            static::assertStringContainsString('/'.$entityId.'/', $url, 'URL should contain the correct entity ID in the path');

            return $entityId;
        });

        sort($entityIds);
        sort($expectedIds);
        static::assertSame($expectedIds, $entityIds, 'Each row should link to its own entity');
    }

    public function testDefaultRowActionWithDoubleClickTrigger(): void
    {
        $category = CategoryFactory::createOne();

        // use a different controller that sets a double click trigger
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionClickTriggerDoubleCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertCount(1, $rows);

        $firstRow = $rows->first();
        static::assertSame((string) $category->getId(), $firstRow->attr('data-id'));
        static::assertNotNull($firstRow->attr('data-default-action-url'), 'Row should have data-default-action-url attribute');
        static::assertStringContainsString(sprintf('/%d/edit', $category->getId()), $firstRow->attr('data-default-action-url'), 'URL should contain edit action');

        $table = $crawler->filter('table.datagrid');
        static::assertEquals('double', $table->attr('data-default-action-trigger'), 'Click trigger should be double');
    }
}
