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

use Symfony\Component\DomCrawler\Crawler;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class SplitConfigurationTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'split_configuration'));
    }

    public function testConfiguredEntities()
    {
        $backendConfig = $this->client->getContainer()->getParameter('easyadmin.config');

        $this->assertEquals(array('Category', 'Product'), array_keys($backendConfig['entities']));
        $this->assertEquals('Categories', $backendConfig['entities']['Category']['label']);
    }
}
