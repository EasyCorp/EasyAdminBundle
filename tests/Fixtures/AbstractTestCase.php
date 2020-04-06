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
    protected static $client;
    protected static $options = [];

    protected function setUp(): void
    {
        $this->initClient();
        $this->initDatabase();
    }

    protected function initClient(array $options = [])
    {
        static::$client = static::createClient($options + static::$options);
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

        if (!file_exists($originalDbPath)) {
            throw new \RuntimeException(sprintf("The fixtures file used for the tests (%s) doesn't exist. This means that the execution of the bootstrap.php script that generates that file failed. Open %s/bootstrap.php and replace `NullOutput as ConsoleOutput` by `ConsoleOutput` to see the actual errors in the console.", $originalDbPath, realpath(__DIR__.'/..')));
        }

        copy($originalDbPath, $targetDbPath);
    }

    /**
     * @param array $queryParameters
     *
     * @return Crawler
     */
    protected function getBackendPage(array $queryParameters, array $serverParameters = [])
    {
        return static::$client->request('GET', '/admin/?'.http_build_query($queryParameters, '', '&'), [], [], $serverParameters);
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

    /**
     * @return Crawler
     */
    protected function requestListViewAsLoggedUser($entityName = 'Category', string $username = 'admin', string $password = 'pa$$word')
    {
        return $this->getBackendPage([
            'action' => 'list',
            'entity' => $entityName,
            'view' => 'list',
        ], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestShowViewAsLoggedUser($entityName = 'Category', $entityId = 200, string $username = 'admin', string $password = 'pa$$word')
    {
        return $this->getBackendPage([
            'action' => 'show',
            'entity' => $entityName,
            'id' => $entityId,
        ], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestSearchViewAsLoggedUser($searchQuery = 'cat', $entityName = 'Category', string $username = 'admin', string $password = 'pa$$word')
    {
        return $this->getBackendPage([
            'action' => 'search',
            'entity' => $entityName,
            'query' => $searchQuery,
        ], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestNewViewAsLoggedUser($entityName = 'Category', string $username = 'admin', string $password = 'pa$$word')
    {
        return $this->getBackendPage([
            'action' => 'new',
            'entity' => $entityName,
        ], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ]);
    }

    /**
     * @return Crawler
     */
    protected function requestEditViewAsLoggedUser($entityName = 'Category', $entityId = '200', string $username = 'admin', string $password = 'pa$$word')
    {
        return $this->getBackendPage([
            'action' => 'edit',
            'entity' => $entityName,
            'id' => $entityId,
        ], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ]);
    }
}
