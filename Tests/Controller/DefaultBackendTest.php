<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class DefaultBackendTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'default_backend'));
    }

    public function testBackendHomepageRedirection()
    {
        $this->client->request('GET', '/admin/');

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            '/admin/?action=list&entity=Category',
            $this->client->getResponse()->getTargetUrl(),
            'The backend homepage redirects to the "list" view of the first configured entity ("Category").'
        );
    }

    public function testDefaultCssFilesAreLinked()
    {
        $cssFiles = array(
            '/bundles/easyadmin/stylesheet/bootstrap.min.css',
            '/bundles/easyadmin/stylesheet/font-awesome.min.css',
            '/admin/_css/admin.css',
        );

        $crawler = $this->getBackendHomepage();

        foreach ($cssFiles as $i => $url) {
            $this->assertEquals($url, $crawler->filterXPath('//link[@rel="stylesheet"]')->eq($i)->attr('href'));
        }
    }

    public function testLogo()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertEquals('Easy Admin', $crawler->filter('#header-logo a')->text());
        $this->assertEquals('/admin/', $crawler->filter('#header-logo a')->attr('href'));
        $this->assertEquals('short', $crawler->filter('#header-logo a')->attr('class'));
    }

    public function testMainMenuItems()
    {
        $menuItems = array(
            'Category' => '/admin/?entity=Category&action=list&view=list',
            'Image' => '/admin/?entity=Image&action=list&view=list',
            'Purchase' => '/admin/?entity=Purchase&action=list&view=list',
            'PurchaseItem' => '/admin/?entity=PurchaseItem&action=list&view=list',
            'Product' => '/admin/?entity=Product&action=list&view=list',
        );

        $crawler = $this->getBackendHomepage();

        $i = 0;
        foreach ($menuItems as $label => $url) {
            $this->assertEquals($label, $crawler->filter('#header-menu li a')->eq($i)->text());
            $this->assertEquals($url, $crawler->filter('#header-menu li a')->eq($i)->attr('href'));

            $i++;
        }
    }

    public function testAdminCssFile()
    {
        $this->client->request('GET', '/admin/_css/admin.css');

        $this->assertEquals('text/css; charset=UTF-8', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(19, substr_count($this->client->getResponse()->getContent(), '#E67E22'), 'The admin.css file uses the default brand color.');
        // #222222 color is only used by the "dark" color scheme, not the "light" one
        $this->assertEquals(15, substr_count($this->client->getResponse()->getContent(), '#222222'), 'The admin.css file uses the dark color scheme.');
    }
}
