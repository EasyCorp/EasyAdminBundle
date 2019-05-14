<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class RawFieldTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'raw_field'];

    public function testListViewRawField()
    {
        $crawler = $this->requestListView('Product');

        $this->assertRegExp('/\s*<ul>\s*(<li>.*<\/li>\s*){2}\s*<\/ul>/', $crawler->filter('#main table td.raw')->eq(0)->html());
    }

    public function testShowViewRawField()
    {
        $crawler = $this->requestShowView('Product', 50);

        $this->assertRegExp('/\s*<ul>\s*(<li>.*<\/li>\s*){2}\s*<\/ul>/', $crawler->filter('#main .form-control')->eq(0)->html());
    }
}
