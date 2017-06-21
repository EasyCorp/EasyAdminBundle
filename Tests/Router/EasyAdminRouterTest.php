<?php

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

        $this->router = $this->client->getContainer()->get('easyadmin.router');
    }

    /**
     * @dataProvider provideEntities
     */
    public function testRouter($entity, $action, $expectEntity, array $parameters, array $expectParameters = array())
    {
        $url = $this->router->generate($entity, $action, $parameters);

        self::assertContains('entity='.$expectEntity, $url);
        self::assertContains('action='.$action, $url);

        foreach (array_merge($parameters, $expectParameters) as $key => $value) {
            self::assertContains($key.'='.$value, $url);
        }
    }

    public function provideEntities()
    {
        $product = new Product();
        $ref = new \ReflectionClass($product);
        $ref->getProperty('id')->setValue($product, 1);

        return array(
            array('AppTestBundle\Entity\FunctionalTests\Category', 'new', 'Category', array('modal' => 1)),
            array('Product', 'new', 'Product', array('entity' => 'Category'), array('entity' => 'product')),
            array($product, 'show', 'Product', array(), array('id' => 1)),
        );
    }
}
