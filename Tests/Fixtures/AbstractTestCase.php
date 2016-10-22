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
     * @param array $queryParameters
     *
     * @return Crawler
     */
    protected function getBackendPage(array $queryParameters)
    {
        return $this->client->request('GET', '/admin/?'.http_build_query($queryParameters, '', '&'));
    }

    /**
     * @return Crawler
     */
    protected function getBackendHomepage()
    {
        return $this->getBackendPage(array('entity' => 'Category', 'view' => 'list'));
    }

    /**
     * @return Crawler
     */
    protected function requestListView($entityName = 'Category')
    {
        return $this->getBackendPage(array(
            'action' => 'list',
            'entity' => $entityName,
            'view' => 'list',
        ));
    }

    /**
     * @return Crawler
     */
    protected function requestShowView($entityName = 'Category', $entityId = 200)
    {
        return $this->getBackendPage(array(
            'action' => 'show',
            'entity' => $entityName,
            'id' => $entityId,
        ));
    }

    /**
     * @return Crawler
     */
    protected function requestSearchView($searchQuery = 'cat', $entityName = 'Category')
    {
        return $this->getBackendPage(array(
            'action' => 'search',
            'entity' => $entityName,
            'query' => $searchQuery,
        ));
    }

    /**
     * @return Crawler
     */
    protected function requestNewView($entityName = 'Category')
    {
        return $this->getBackendPage(array(
            'action' => 'new',
            'entity' => $entityName,
        ));
    }

    /**
     * @return Crawler
     */
    protected function requestEditView($entityName = 'Category', $entityId = '200')
    {
        return $this->getBackendPage(array(
            'action' => 'edit',
            'entity' => $entityName,
            'id' => $entityId,
        ));
    }
}
