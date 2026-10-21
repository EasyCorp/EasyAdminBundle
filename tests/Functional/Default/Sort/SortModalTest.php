<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\BlogPostSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;

/**
 * Tests the sort modal displayed on small screens, where the datagrid header is hidden.
 */
class SortModalTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return BlogPostSortCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        // the sort modal is only rendered when the index page has results
        BlogPostFactory::createOne(['title' => 'Post Apple']);
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
        $crawler = $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query(['sort' => ['title' => 'ASC']]));

        $this->assertResponseIsSuccessful();

        $sortedFieldLink = $crawler->filter('#modal-sort a[data-column="title"]');
        $this->assertStringContainsString('sort[title]=DESC', urldecode($sortedFieldLink->attr('href')));
        $this->assertSame('true', $sortedFieldLink->attr('aria-current'));

        $otherFieldLink = $crawler->filter('#modal-sort a[data-column="views"]');
        $this->assertStringContainsString('sort[views]=DESC', urldecode($otherFieldLink->attr('href')));
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
