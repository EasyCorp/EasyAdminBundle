<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Rendering tests for the <twig:ea:Pagination> component (using its raw mode,
 * which doesn't require an admin context or an EntityPaginator).
 */
class PaginationTest extends AbstractFieldFunctionalTestCase
{
    private function renderPagination(string $template): Crawler
    {
        return new Crawler(static::getContainer()->get('twig')->createTemplate($template)->render());
    }

    public function testDefaultRendering(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" totalItems="100" pageSize="10" urlPattern="?page={page}" />');

        // same DOM structure and CSS classes as the legacy crud/paginator.html.twig template
        $this->assertCount(1, $crawler->filter('div.list-pagination'));
        $this->assertCount(1, $crawler->filter('div.list-pagination > div.list-pagination-counter'));
        $this->assertCount(1, $crawler->filter('div.list-pagination > nav.pager.list-pagination-paginator > ul.pagination'));
        $this->assertSame('100', $crawler->filter('.list-pagination-counter strong')->text());

        $this->assertCount(1, $crawler->filter('ul.pagination li.page-item.page-item-previous a.page-link'));
        $this->assertCount(1, $crawler->filter('ul.pagination li.page-item.page-item-next a.page-link'));
        $this->assertSame('?page=2', $crawler->filter('li.page-item-previous a')->attr('href'));
        $this->assertSame('?page=4', $crawler->filter('li.page-item-next a')->attr('href'));
        $this->assertSame('Previous', $crawler->filter('li.page-item-previous .btn-label')->text());
        $this->assertSame('Next', $crawler->filter('li.page-item-next .btn-label')->text());

        // additive accessibility attributes
        $this->assertSame('Pagination', $crawler->filter('nav')->attr('aria-label'));
        $this->assertSame('prev', $crawler->filter('li.page-item-previous a')->attr('rel'));
        $this->assertSame('next', $crawler->filter('li.page-item-next a')->attr('rel'));
        $this->assertSame('3', $crawler->filter('.page-item.active a.page-link')->text());
        $this->assertSame('page', $crawler->filter('.page-item.active a.page-link')->attr('aria-current'));

        // prev + pages 1..6 + gap + page 10 + next
        $this->assertCount(10, $crawler->filter('li.page-item'));
        $this->assertSame(['1', '2', '3', '4', '5', '6', '10'], $crawler->filter('li.page-item a[href^="?page="]:not([rel])')->each(static fn (Crawler $link) => $link->text()));
    }

    public function testFirstPageDisablesThePreviousLink(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="1" lastPage="10" urlPattern="?page={page}" />');

        $this->assertCount(1, $crawler->filter('li.page-item-previous.disabled'));
        $previousLink = $crawler->filter('li.page-item-previous a');
        $this->assertSame('#', $previousLink->attr('href'));
        $this->assertSame('true', $previousLink->attr('aria-disabled'));
        $this->assertSame('-1', $previousLink->attr('tabindex'));
        $this->assertNull($previousLink->attr('rel'));

        $this->assertStringContainsString('first-page', $crawler->filter('nav')->attr('class'));
        $this->assertStringNotContainsString('last-page', $crawler->filter('nav')->attr('class'));
    }

    public function testLastPageDisablesTheNextLink(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="10" lastPage="10" urlPattern="?page={page}" />');

        $this->assertCount(1, $crawler->filter('li.page-item-next.disabled'));
        $nextLink = $crawler->filter('li.page-item-next a');
        $this->assertSame('#', $nextLink->attr('href'));
        $this->assertSame('true', $nextLink->attr('aria-disabled'));
        $this->assertStringContainsString('last-page', $crawler->filter('nav')->attr('class'));
    }

    public function testGapsAreRenderedAsEllipsis(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="18" lastPage="35" urlPattern="?page={page}" />');

        $gaps = $crawler->filter('li.page-item.disabled span.page-link');
        $this->assertCount(2, $gaps);
        $this->assertSame('…', $gaps->first()->filter('[aria-hidden="true"]')->text());
        $this->assertSame('More pages', $gaps->first()->filter('.visually-hidden')->text());
    }

    public function testCounterIsEmptyWhenResultsCountIsDisabled(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" totalItems="100" pageSize="10" urlPattern="?page={page}" :showResultsCount="false" />');

        // the counter element is always rendered (it's needed for the layout) but it's empty
        $this->assertCount(1, $crawler->filter('.list-pagination-counter'));
        $this->assertSame('', trim($crawler->filter('.list-pagination-counter')->text()));
    }

    public function testCounterIsEmptyWhenTheResultsCountIsUnknown(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" lastPage="10" urlPattern="?page={page}" />');

        $this->assertSame('', trim($crawler->filter('.list-pagination-counter')->text()));
    }

    public function testHidingPageNumbersOnlyRendersTheCurrentPage(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" lastPage="10" urlPattern="?page={page}" :showPageNumbers="false" />');

        // prev + active page + next
        $this->assertCount(3, $crawler->filter('li.page-item'));
        $this->assertSame('3', $crawler->filter('li.page-item.active a')->text());
    }

    public function testFirstAndLastButtons(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" lastPage="10" urlPattern="?page={page}" showFirstLast />');

        $this->assertSame('First', $crawler->filter('li.page-item-first .btn-label')->text());
        $this->assertSame('Last', $crawler->filter('li.page-item-last .btn-label')->text());
        $this->assertSame('?page=1', $crawler->filter('li.page-item-first a')->attr('href'));
        $this->assertSame('?page=10', $crawler->filter('li.page-item-last a')->attr('href'));
    }

    public function testFirstAndLastButtonsAreDisabledOnEdges(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="1" lastPage="10" urlPattern="?page={page}" showFirstLast />');

        $this->assertCount(1, $crawler->filter('li.page-item-first.disabled'));
        $this->assertSame('true', $crawler->filter('li.page-item-first a')->attr('aria-disabled'));
        $this->assertCount(0, $crawler->filter('li.page-item-last.disabled'));
    }

    public function testHidingPreviousNextLabelsKeepsThemVisuallyHidden(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" lastPage="10" urlPattern="?page={page}" :showPreviousNextLabels="false" />');

        $this->assertCount(0, $crawler->filter('li.page-item-previous .btn-label'));
        $this->assertSame('Previous', $crawler->filter('li.page-item-previous .visually-hidden')->text());
        $this->assertSame('Next', $crawler->filter('li.page-item-next .visually-hidden')->text());
    }

    public function testSmallSizeAndRadius(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" lastPage="10" urlPattern="?page={page}" size="sm" radius="full" />');

        $this->assertCount(1, $crawler->filter('ul.pagination.pagination-sm'));
        $this->assertCount(0, $crawler->filter('a.page-link:not(.ea-rounded-full)'));
    }

    public function testPreviousLabelBlockOverride(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" lastPage="10" urlPattern="?page={page}"><twig:block name="previous_label">Back</twig:block></twig:ea:Pagination>');

        $this->assertSame('Back', $crawler->filter('li.page-item-previous .btn-label')->text());
        $this->assertSame('Next', $crawler->filter('li.page-item-next .btn-label')->text());
    }

    public function testExtraAttributesAreMergedOnTheRootElement(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="3" lastPage="10" urlPattern="?page={page}" class="my-pagination" id="foo" />');

        $rootElement = $crawler->filter('div.list-pagination');
        $this->assertStringContainsString('my-pagination', $rootElement->attr('class'));
        $this->assertSame('foo', $rootElement->attr('id'));
    }

    public function testSinglePageRendersNoNav(): void
    {
        $crawler = $this->renderPagination('<twig:ea:Pagination currentPage="1" totalItems="5" pageSize="10" urlPattern="?page={page}" />');

        $this->assertCount(0, $crawler->filter('nav'));
        $this->assertSame('5', $crawler->filter('.list-pagination-counter strong')->text());
    }
}
