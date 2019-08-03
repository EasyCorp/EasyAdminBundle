<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use AppTestBundle\Entity\FunctionalTests\Product;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class IntlDateTimeNumericFormatsTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'intl_formats'];

    public function testListViewFormatting()
    {
        $crawler = $this->requestListView('Product');

        /** @var Product $product */
        $product = self::$client->getContainer()->get('doctrine')->getRepository(Product::class)->find(100);
        $formattedCreatedAt = \IntlDateFormatter::create('es', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE)->format($product->getCreatedAt()->getTimestamp());
        $formattedPrice = \NumberFormatter::create('es', \NumberFormatter::SCIENTIFIC)->format($product->getPrice());

        $this->assertContains($formattedCreatedAt, trim($crawler->filter('.datagrid tr[data-id="100"] td.datetime')->text()));
        $this->assertContains($formattedPrice, trim($crawler->filter('.datagrid tr[data-id="100"] td.float')->text()));
    }

    public function testShowViewFormatting()
    {
        $crawler = $this->requestShowView('Product', 100);

        /** @var Product $product */
        $product = self::$client->getContainer()->get('doctrine')->getRepository(Product::class)->find(100);
        $formattedCreatedAt = \IntlDateFormatter::formatObject($product->getCreatedAt(), 'MMMM yyyy', 'es');
        $formattedPrice = \NumberFormatter::create('es', \NumberFormatter::SPELLOUT)->format($product->getPrice());

        $this->assertContains($formattedCreatedAt, trim($crawler->filter('.field-datetime time')->text()));
        $this->assertContains($formattedPrice, trim($crawler->filter('.field-float .form-control')->text()));
    }
}
