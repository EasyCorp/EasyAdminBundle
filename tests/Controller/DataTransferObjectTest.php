<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class DataTransferObjectTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'data_transfer_object'];

    public function testNewProductDTO(): void
    {
        $crawler = $this->requestNewView('Product');
        $this->assertSame(200, static::$client->getResponse()->getStatusCode());

        $form = $crawler->filter('#main form')->eq(0);
        $this->assertSame('new', trim($form->attr('data-view')));
        $this->assertSame('Product', trim($form->attr('data-entity')));
        $this->assertEmpty($form->attr('data-entity-id'));

        $form = $crawler->selectButton('Save changes')->form();
        static::$client->submit($form, [
            'product[name]' => 'Product X',
            'product[description]' => 'Description X',
            'product[price]' => '1000.00',
            'product[ean]' => '4006381333932',
        ]);
        $this->assertSame(302, static::$client->getResponse()->getStatusCode());

        $crawler = $this->requestListView('Product');
        $this->assertContains('101 results', $crawler->filter('.list-pagination')->text(null, true));
        $crawler = $this->requestSearchView('Product X', 'Product');
        $this->assertCount(1, $crawler->filter('.table tbody tr'));
    }

    public function testEditProductPriceDTO(): void
    {
        $crawler = $this->requestEditView('Product', '1');
        $this->assertSame(200, static::$client->getResponse()->getStatusCode());

        $form = $crawler->filter('#main form')->eq(0);
        $this->assertSame('edit', trim($form->attr('data-view')));
        $this->assertSame('Product', trim($form->attr('data-entity')));
        $this->assertEmpty($form->attr('data-entity-id'));

        $form = $crawler->selectButton('Save changes')->form();
        static::$client->submit($form, [
            'product[name]' => 'Product X',
        ]);
        $this->assertSame(302, static::$client->getResponse()->getStatusCode());

        $crawler = $this->requestSearchView('Product X', 'Product');
        $this->assertCount(1, $crawler->filter('.table tbody tr'));
    }
}
