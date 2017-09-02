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

class RtlTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'rtl'));
    }

    public function testRtlAutodetection()
    {
        $this->getBackendHomepage();

        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();
        $this->assertTrue($backendConfig['design']['rtl'], 'RTL is enabled automatically for the "ar" locale.');
    }

    public function testRtlWebAssets()
    {
        $crawler = $this->getBackendHomepage();

        $this->assertSame(
            '/bundles/easyadmin/stylesheet/easyadmin-all.min.css',
            $crawler->filter('link[rel="stylesheet"]')->eq(0)->attr('href')
        );

        $this->assertSame(
            '/bundles/easyadmin/stylesheet/bootstrap-rtl.min.css',
            $crawler->filter('link[rel="stylesheet"]')->eq(1)->attr('href')
        );

        $this->assertSame(
            '/bundles/easyadmin/stylesheet/adminlte-rtl.min.css',
            $crawler->filter('link[rel="stylesheet"]')->eq(2)->attr('href')
        );
    }
}
