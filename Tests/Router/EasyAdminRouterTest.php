<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Router;

use AppTestBundle\Entity\FunctionalTests\Product;
use JavierEguiluz\Bundle\EasyAdminBundle\Router\EasyAdminRouter;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class EasyAdminRouterTest extends AbstractTestCase
{
    /**
     * @var EasyAdminRouter
     */
    private $router;

    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'default_backend'));

        // don't use $this->client->getContainer()->get('easyadmin.router');
        // to avoid ServiceCircularReferenceException errors
        $this->router = new EasyAdminRouter(
            $this->client->getContainer()->get('easyadmin.config.manager'),
            $this->client->getContainer()->get('router'),
            $this->client->getContainer()->get('property_accessor'),
            $this->client->getContainer()->get('request_stack')
        );
    }

    /**
     * @dataProvider provideEntities
     */
    public function testUrlGeneration($entity, $action, $expectEntity, array $parameters = array(), array $expectParameters = array())
    {
        $url = $this->router->generate($entity, $action, $parameters);

        $this->assertContains('entity='.$expectEntity, $url);
        $this->assertContains('action='.$action, $url);

        foreach (array_merge($parameters, $expectParameters) as $key => $value) {
            $this->assertContains($key.'='.$value, $url);
        }
    }

    /**
     * @dataProvider provideUndefinedEntities
     *
     * @expectedException \JavierEguiluz\Bundle\EasyAdminBundle\Exception\UndefinedEntityException
     */
    public function testUndefinedEntityException($entity, $action)
    {
        $this->router->generate($entity, $action);
    }

    public function provideEntities()
    {
        $product = new Product();
        $ref = new \ReflectionClass($product);
        $refPropertyId = $ref->getProperty('id');
        $refPropertyId->setAccessible(true);
        $refPropertyId->setValue($product, 1);

        return array(
            array('AppTestBundle\Entity\FunctionalTests\Category', 'new', 'Category'),
            array('Product', 'new', 'Product', array('entity' => 'Category'), array('entity' => 'Product')),
            array($product, 'show', 'Product', array('modal' => 1), array('id' => 1)),
        );
    }

    public function provideUndefinedEntities()
    {
        return array(
            array('ThisEntityDoesNotExist', 'new'),
        );
    }
}
