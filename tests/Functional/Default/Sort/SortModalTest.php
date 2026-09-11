<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortTestEntityCrudController;

/**
 * Tests the sort modal displayed on small screens, where the datagrid header is hidden.
 */
class SortModalTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SortTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testSortModalTriggerIsRendered(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.datagrid-sort.d-md-none button[data-bs-toggle="modal"][data-bs-target="#modal-sort"]');
        $this->assertSelectorTextSame('.datagrid-sort button .btn-label', 'Sort');
        $this->assertSelectorTextSame('#modal-sort .modal-body h4', 'Sort by');
    }

    public function testSortModalLinksMatchDatagridHeaderLinks(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        $headerLinks = $crawler->filter('table.datagrid thead th a')->each(static fn ($link) => $link->attr('href'));
        $modalLinks = $crawler->filter('#modal-sort a[data-column]')->each(static fn ($link) => $link->attr('href'));

        $this->assertNotEmpty($modalLinks);
        $this->assertSame($headerLinks, $modalLinks);
    }

    public function testSortModalLinksToggleTheSortDirection(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query(['sort' => ['textField' => 'ASC']]));

        $this->assertResponseIsSuccessful();

        $sortedFieldLink = $crawler->filter('#modal-sort a[data-column="textField"]');
        $this->assertStringContainsString('sort[textField]=DESC', urldecode($sortedFieldLink->attr('href')));
        $this->assertSame('true', $sortedFieldLink->attr('aria-current'));

        $otherFieldLink = $crawler->filter('#modal-sort a[data-column="integerField"]');
        $this->assertStringContainsString('sort[integerField]=DESC', urldecode($otherFieldLink->attr('href')));
        $this->assertNull($otherFieldLink->attr('aria-current'));
    }

    public function testSortModalIsNotRenderedWhenThereAreNoResults(): void
    {
        $this->client->request('GET', $this->generateIndexUrl().'?query=this-query-does-not-match-any-result');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#modal-sort');
        $this->assertSelectorNotExists('.datagrid-sort');
    }
}
