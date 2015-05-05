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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultBackendTest extends WebTestCase
{
    public function testIndexRedirectsToTheFirstEntityListing()
    {
        $client = static::createClient();
        $client->request('GET', '/admin/');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            '/admin/?action=list&entity=Category',
            $client->getResponse()->getTargetUrl()
        );
    }

    public function testDefaultCssFilesAreLinked()
    {
        $cssFiles = array(
            '/bundles/easyadmin/stylesheet/bootstrap.min.css',
            '/bundles/easyadmin/stylesheet/font-awesome.min.css',
            '/_css/admin.css',
        );

        $client = static::createClient();
        $client->followRedirects(true);
        $crawler = $client->request('GET', '/admin/');

        foreach ($cssFiles as $i => $url) {
            $this->assertEquals($url, $crawler->filterXPath('//link[@rel="stylesheet"]')->eq($i)->attr('href'));
        }
    }

    public function testLogo()
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $crawler = $client->request('GET', '/admin/');

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

        $client = static::createClient();
        $client->followRedirects(true);
        $crawler = $client->request('GET', '/admin/');

        $i = 0;
        foreach ($menuItems as $label => $url) {
            $this->assertEquals($label, $crawler->filter('#header-menu li a')->eq($i)->text());
            $this->assertEquals($url, $crawler->filter('#header-menu li a')->eq($i)->attr('href'));

            $i++;
        }
    }
}
