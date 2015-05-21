<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Tests;

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class DefaultBackendTest extends AbstractTestCase
{
    public function testIndexRedirectsToTheFirstEntityListing()
    {
        $this->doGetRequest();

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            '/admin/?action=list&entity=Category',
            $this->client->getResponse()->getTargetUrl()
        );
    }

    public function testDefaultCssFilesAreLinked()
    {
        $cssFiles = array(
            '/bundles/easyadmin/stylesheet/bootstrap.min.css',
            '/bundles/easyadmin/stylesheet/font-awesome.min.css',
            '/admin/_css/admin.css',
        );

        $this->client->followRedirects(true);
        $crawler = $this->doGetRequest();

        foreach ($cssFiles as $i => $url) {
            $this->assertEquals($url, $crawler->filterXPath('//link[@rel="stylesheet"]')->eq($i)->attr('href'));
        }
    }

    public function testLogo()
    {
        $this->client->followRedirects(true);
        $crawler = $this->doGetRequest();

        $this->assertEquals('ACME Backend', $crawler->filter('#header-logo a')->text());
        $this->assertEquals('/admin/', $crawler->filter('#header-logo a')->attr('href'));
        $this->assertEquals('medium', $crawler->filter('#header-logo a')->attr('class'));
    }

    public function testMainMenuItems()
    {
        $menuItems = array(
            'Categories' => '/admin/?entity=Category&action=list&view=list',
            'Images' => '/admin/?entity=Image&action=list&view=list',
            'Purchases' => '/admin/?entity=Purchase&action=list&view=list',
            'Purchase Items' => '/admin/?entity=PurchaseItem&action=list&view=list',
            'Products' => '/admin/?entity=Product&action=list&view=list',
        );

        $this->client->followRedirects(true);
        $crawler = $this->doGetRequest();

        $i = 0;
        foreach ($menuItems as $label => $url) {
            $this->assertEquals($label, $crawler->filter('#header-menu li a')->eq($i)->text());
            $this->assertEquals($url, $crawler->filter('#header-menu li a')->eq($i)->attr('href'));

            $i++;
        }
    }

    public function testUndefinedEntityError()
    {
        $parameters = array(
            'action' => 'list',
            'entity' => 'InexistentEntity',
            'view' => 'list',
        );

        $crawler = $this->doGetRequest($parameters);

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Undefined entity', $crawler->filter('head title')->text());
        $this->assertEquals("The InexistentEntity entity is not defined in\n    the configuration of your backend.", trim($crawler->filter('body.error .container .error-problem p.lead')->text()));
    }

    public function testAdminCss()
    {
        $client = static::createClient();
        $client->request('GET', '/admin/_css/admin.css');

        $this->assertEquals('text/css; charset=UTF-8', $this->client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(21, substr_count($this->client->getResponse()->getContent(), '#123456'), 'The custom brand_color option is used in the admin CSS.');
        // #FAFAFA color is only used by the "light" color scheme, not the "dark" one
        $this->assertEquals(12, substr_count($this->client->getResponse()->getContent(), '#FAFAFA'), 'The selected "light" color scheme is used in the admin CSS.');
    }
}
