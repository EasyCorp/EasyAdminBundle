<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class EntitySortingByAssociationTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'entity_sorting_by_association']);
    }

    public function testListViewSorting()
    {
        $crawler = $this->requestListView();

        $this->assertContains('sorted', $crawler->filter('th:contains("Parent")')->attr('class'));
        $this->assertContains('fa-arrow-down', $crawler->filter('th:contains("Parent") i')->attr('class'));
    }

    public function testSearchViewSorting()
    {
        $crawler = $this->requestSearchView();

        $this->assertContains('sorted', $crawler->filter('th:contains("Parent")')->attr('class'));
        $this->assertContains('fa-arrow-up', $crawler->filter('th:contains("Parent") i')->attr('class'));
    }
}
