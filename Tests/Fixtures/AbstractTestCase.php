<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AbstractTestCase. Code copied from
 * https://github.com/Orbitale/CmsBundle/blob/master/Tests/Fixtures/AbstractTestCase.php
 * (c) Alexandre Rock Ancelet <alex@orbitale.io>.
 */
abstract class AbstractTestCase extends WebTestCase
{
    /** @var Client */
    protected $client;

    protected function setUp()
    {
        $this->initClient();
        $this->initDatabase();
    }

    protected function tearDown()
    {
        $this->client = null;
    }

    protected function initClient(array $options = array())
    {
        $this->client = static::createClient($options);
    }

    /**
     * It ensures that the database contains the original fixtures of the
     * application. This way tests can modify its contents safely without
     * interfering with subsequent tests.
     */
    protected function initDatabase()
    {
        $buildDir = __DIR__.'/../../build';
        copy($buildDir.'/original_test.db', $buildDir.'/test.db');
    }

    /**
     * @param array $parameters
     *
     * @return Crawler
     */
    protected function getBackendPage(array $parameters)
    {
        return $this->client->request('GET', '/admin/?'.http_build_query($parameters, '', '&'));
    }

    /**
     * @return Crawler
     */
    protected function getBackendHomepage()
    {
        return $this->getBackendPage(array('entity' => 'Category', 'view' => 'list'));
    }
}
