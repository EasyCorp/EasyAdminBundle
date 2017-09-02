<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class RawFieldTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'raw_field'));
    }

    public function testListViewRawField()
    {
        $crawler = $this->requestListView('Product');

        $this->assertRegExp('/\s*<ul>\s*(<li>.*<\/li>\s*){2}\s*<\/ul>/', $crawler->filter('#main table td[data-label="Html features"]')->eq(0)->html());
    }

    public function testShowViewRawField()
    {
        $crawler = $this->requestShowView('Product', 50);

        $this->assertRegExp('/\s*<ul>\s*(<li>.*<\/li>\s*){2}\s*<\/ul>/', $crawler->filter('#main .form-control')->eq(0)->html());
    }
}
