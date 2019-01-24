<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class DqlFilterTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'dql_filter']);
    }

    public function testListDqlFilter()
    {
        $crawler = $this->requestListView();

        $this->assertCount(4, $crawler->filter('#main .table tbody tr'));
        $this->assertSame(
            ['54', '53', '52', '51'],
            $crawler->filter('#main .table tbody tr')->extract('data-id')
        );
    }

    public function testSearchDqlFilter()
    {
        $crawler = $this->requestSearchView();

        $this->assertCount(11, $crawler->filter('#main .table tbody tr'));
        $this->assertSame(
            ['29', '28', '27', '26', '25', '24', '23', '22', '21', '20', '2'],
            $crawler->filter('#main .table tbody tr')->extract('data-id')
        );
    }

    public function testAutocompleteDqlFilter()
    {
        $this->getBackendPage([
            'action' => 'autocomplete',
            'entity' => 'Category',
            'query' => 21,
        ]);

        // the results are only all parent categories
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(
            [
                ['id' => 21, 'text' => 'Parent Category #21'],
            ],
            $response['results']
        );
    }
}
