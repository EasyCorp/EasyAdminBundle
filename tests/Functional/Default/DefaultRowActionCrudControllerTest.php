<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionDetailCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionDisabledCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionFallbackCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DefaultRowAction\DefaultRowActionMissingCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

class DefaultRowActionCrudControllerTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

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

        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    public function testDefaultRowActionWithEditAction(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // by default, the default row action is Action::EDIT
        // check that rows have the data-default-action-url attribute and the class
        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(0, $rows->count(), 'There should be at least one row in the table');

        $firstRow = $rows->first();
        static::assertNotNull($firstRow->attr('data-default-action-url'), 'Row should have data-default-action-url attribute');
        static::assertStringContainsString('ea-clickable-row', $firstRow->attr('class'), 'Row should have ea-clickable-row class');
        static::assertStringContainsString('/edit', $firstRow->attr('data-default-action-url'), 'URL should contain edit action');
    }

    public function testDefaultRowActionWithDetailAction(): void
    {
        // use a different controller that sets Action::DETAIL as default
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionDetailCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(0, $rows->count(), 'There should be at least one row in the table');

        $firstRow = $rows->first();
        static::assertNotNull($firstRow->attr('data-default-action-url'), 'Row should have data-default-action-url attribute');
        static::assertStringContainsString('ea-clickable-row', $firstRow->attr('class'), 'Row should have ea-clickable-row class');
        static::assertStringContainsString('/'.$firstRow->attr('data-id'), $firstRow->attr('data-default-action-url'), 'URL should contain the entity ID for detail action');
    }

    public function testDefaultRowActionDisabled(): void
    {
        // use a different controller that disables the default row action
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionDisabledCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(0, $rows->count(), 'There should be at least one row in the table');

        $firstRow = $rows->first();
        static::assertNull($firstRow->attr('data-default-action-url'), 'Row should NOT have data-default-action-url attribute when disabled');
        static::assertStringNotContainsString('ea-clickable-row', $firstRow->attr('class') ?? '', 'Row should NOT have ea-clickable-row class when disabled');
    }

    public function testDefaultRowActionWithMissingAction(): void
    {
        // use a controller where the edit action is disabled and detail is not added
        // the default row action fallback is [EDIT, DETAIL] but neither are available
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionMissingCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(0, $rows->count(), 'There should be at least one row in the table');

        $firstRow = $rows->first();
        static::assertNull($firstRow->attr('data-default-action-url'), 'Row should NOT have data-default-action-url when no fallback action is available');
        static::assertStringNotContainsString('ea-clickable-row', $firstRow->attr('class') ?? '', 'Row should NOT have ea-clickable-row class when no fallback action is available');
    }

    public function testDefaultRowActionFallbackToDetail(): void
    {
        // use a controller where EDIT is disabled but DETAIL is enabled
        // the default row action should fallback from EDIT to DETAIL
        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, DefaultRowActionFallbackCrudController::class));

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(0, $rows->count(), 'There should be at least one row in the table');

        $firstRow = $rows->first();
        static::assertNotNull($firstRow->attr('data-default-action-url'), 'Row should have data-default-action-url with fallback to DETAIL');
        static::assertStringContainsString('ea-clickable-row', $firstRow->attr('class'), 'Row should have ea-clickable-row class');
        static::assertStringContainsString('/'.$firstRow->attr('data-id'), $firstRow->attr('data-default-action-url'), 'URL should contain the entity ID for detail action (fallback)');
    }

    public function testDefaultRowActionUrlIsCorrectForEachEntity(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $rows = $crawler->filter('table.datagrid tbody tr[data-id]');
        static::assertGreaterThan(1, $rows->count(), 'There should be multiple rows in the table');

        // each row should have a different entity ID in the URL
        $entityIds = [];
        $rows->each(static function ($row) use (&$entityIds) {
            $entityId = $row->attr('data-id');
            $url = $row->attr('data-default-action-url');

            static::assertNotNull($url, 'Each row should have a data-default-action-url');
            static::assertStringContainsString('/'.$entityId.'/', $url, 'URL should contain the correct entity ID in the path');

            $entityIds[] = $entityId;
        });

        // verify all entity IDs are unique
        static::assertCount(\count(array_unique($entityIds)), $entityIds, 'Each row should have a unique entity ID');
    }
}
