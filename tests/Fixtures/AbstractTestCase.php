<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures;

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

    protected function initClient(array $options = [])
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
        $originalDbPath = $buildDir.'/original_test.db';
        $targetDbPath = $buildDir.'/test.db';

        if (!\file_exists($originalDbPath)) {
            throw new \RuntimeException(\sprintf("The fixtures file used for the tests (%s) doesn't exist. This means that the execution of the bootstrap.php script that generates that file failed. Open %s/bootstrap.php and replace `NullOutput as ConsoleOutput` by `ConsoleOutput` to see the actual errors in the console.", $originalDbPath, \realpath(__DIR__.'/..')));
        }

        \copy($originalDbPath, $targetDbPath);
    }

    /**
     * @param array $queryParameters
     *
     * @return Crawler
     */
    protected function getBackendPage(array $queryParameters)
    {
        return $this->client->request('GET', '/admin/?'.\http_build_query($queryParameters, '', '&'));
    }

    /**
     * @return Crawler
     */
    protected function getBackendHomepage()
    {
        return $this->getBackendPage(['entity' => 'Category', 'view' => 'list']);
    }

    /**
     * @return Crawler
     */
    protected function requestListView($entityName = 'Category')
    {
        return $this->getBackendPage([
            'action' => 'list',
            'entity' => $entityName,
            'view' => 'list',
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestShowView($entityName = 'Category', $entityId = 200)
    {
        return $this->getBackendPage([
            'action' => 'show',
            'entity' => $entityName,
            'id' => $entityId,
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestSearchView($searchQuery = 'cat', $entityName = 'Category')
    {
        return $this->getBackendPage([
            'action' => 'search',
            'entity' => $entityName,
            'query' => $searchQuery,
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestNewView($entityName = 'Category')
    {
        return $this->getBackendPage([
            'action' => 'new',
            'entity' => $entityName,
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestEditView($entityName = 'Category', $entityId = '200')
    {
        return $this->getBackendPage([
            'action' => 'edit',
            'entity' => $entityName,
            'id' => $entityId,
        ]);
    }
}
