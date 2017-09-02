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

class DefaultMenuTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'default_menu'));
    }

    public function testCustomBackendHomepage()
    {
        $this->client->request('GET', '/admin/');

        $this->assertSame(
            '/admin/?action=list&entity=Category',
            $this->client->getResponse()->headers->get('location')
        );

        $crawler = $this->client->followRedirect();
        $this->assertCount(0, $crawler->filter('.sidebar-menu li.active'));
    }

    public function testMenuIcons()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertCount(5, $crawler->filter('.sidebar-menu i[class*="fa-chevron-circle-right"]'));
    }

    public function testMenuUrls()
    {
        $crawler = $this->getBackendHomepage();

        $urls = array(
            '/admin/?entity=Category&action=list&menuIndex=0&submenuIndex=-1',
            '/admin/?entity=Image&action=list&menuIndex=1&submenuIndex=-1',
            '/admin/?entity=Purchase&action=list&menuIndex=2&submenuIndex=-1',
            '/admin/?entity=PurchaseItem&action=list&menuIndex=3&submenuIndex=-1',
            '/admin/?entity=Product&action=list&menuIndex=4&submenuIndex=-1',
        );

        foreach ($urls as $i => $url) {
            $this->assertSame($url, $crawler->filter('.sidebar-menu li a')->eq($i)->attr('href'));
        }
    }
}
