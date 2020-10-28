<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class FiltersTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'filters'];

    public function testDefaultFiltersButton()
    {
        $crawler = $this->requestListView('Product');

        $this->assertCount(1, $crawler->filter('.global-actions .action-filters'));
        $this->assertCount(1, $crawler->filter('.global-actions .action-filters-button'));
        $this->assertCount(0, $crawler->filter('.global-actions .action-filters-reset'));
    }

    public function testAppliedFiltersButton()
    {
        $crawler = self::$client->request('GET', '/admin/?action%3Flist=&entity=Product&filters%5Bprice%5D%5Bcomparison%5D=%3E&filters%5Bprice%5D%5Bvalue%5D=3.14');

        $this->assertCount(1, $crawler->filter('.global-actions .action-filters'));
        $this->assertCount(1, $crawler->filter('.global-actions .action-filters-button'));
        $this->assertContains('Filters (1)', $crawler->filter('.global-actions .action-filters-button')->text(null, true));
        $this->assertCount(1, $crawler->filter('.global-actions .action-filters-reset'));
    }

    public function testFilterAction()
    {
        $crawler = $this->getBackendPage([
            'action' => 'filters',
            'entity' => 'Product',
            'menuIndex' => 3,
            'submenuIndex' => -1,
            'sortField' => 'id',
            'sortDirection' => 'ASC',
            'filters[name][value]' => 'lorem',
            'filters[price][comparison]' => '>',
            'filters[price][value]' => '3.14',
            'filters[createdAt][comparison]' => '<=',
            'filters[createdAt][value]' => '2019-06-10T10:30:00',
            'filters[categories][comparison]' => '!=',
            'filters[categories][value][]' => '1',
        ]);

        $this->assertCount(1, $crawler->filter('form#filters'));
        $this->assertSame('list', $crawler->filter('#filters input[name="action"]')->attr('value'));
        $this->assertSame('Product', $crawler->filter('#filters input[name="entity"]')->attr('value'));
        $this->assertSame('3', $crawler->filter('#filters input[name="menuIndex"]')->attr('value'));
        $this->assertSame('-1', $crawler->filter('#filters input[name="submenuIndex"]')->attr('value'));
        $this->assertSame('id', $crawler->filter('#filters input[name="sortField"]')->attr('value'));
        $this->assertSame('ASC', $crawler->filter('#filters input[name="sortDirection"]')->attr('value'));

        $this->assertSame('lorem', $crawler->filter('#filters input#filters_name_value')->attr('value'));

        $this->assertSame('is greater than', $crawler->filter('#filters select#filters_price_comparison option[selected="selected"]')->text(null, true));
        $this->assertSame('3.14', $crawler->filter('#filters input#filters_price_value')->attr('value'));

        $this->assertSame('is before or same', $crawler->filter('#filters select#filters_createdAt_comparison option[selected="selected"]')->text(null, true));
        $this->assertSame('2019-06-10T10:30:00', $crawler->filter('#filters input#filters_createdAt_value')->attr('value'));

        $this->assertSame('is not same', $crawler->filter('#filters select#filters_categories_comparison option[selected="selected"]')->text(null, true));
        $this->assertSame('1', $crawler->filter('#filters select#filters_categories_value option[selected="selected"]')->attr('value'));
    }
}
