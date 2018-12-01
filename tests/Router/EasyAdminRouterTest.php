<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Router;

use AppTestBundle\Entity\FunctionalTests\Product;
use EasyCorp\Bundle\EasyAdminBundle\Router\EasyAdminRouter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

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

        $this->initClient(['environment' => 'default_backend']);

        $this->router = $this->client->getContainer()->get('easyadmin.router');
    }

    /**
     * @dataProvider provideEntities
     */
    public function testUrlGeneration($entity, $action, $expectEntity, array $parameters = [], array $expectParameters = [])
    {
        $url = $this->router->generate($entity, $action, $parameters);

        $this->assertContains('entity='.$expectEntity, $url);
        $this->assertContains('action='.$action, $url);

        foreach (\array_merge($parameters, $expectParameters) as $key => $value) {
            $this->assertContains($key.'='.$value, $url);
        }
    }

    /**
     * @dataProvider provideUndefinedEntities
     *
     * @expectedException \EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException
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

        return [
            ['AppTestBundle\Entity\FunctionalTests\Category', 'new', 'Category'],
            ['Product', 'new', 'Product', ['entity' => 'Category'], ['entity' => 'Product']],
            [$product, 'show', 'Product', ['modal' => 1], ['id' => 1]],
        ];
    }

    public function provideUndefinedEntities()
    {
        return [
            ['ThisEntityDoesNotExist', 'new'],
        ];
    }
}
