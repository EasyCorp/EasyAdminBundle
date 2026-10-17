<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\SeparatorsTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\DemoEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setThousandsSeparator() and Crud::setDecimalSeparator() configuration methods.
 */
class SeparatorsTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return SeparatorsTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testSeparatorsConfiguration(): void
    {
        $entity = DemoEntityFactory::createOne(['price' => '1234.56', 'quantity' => 5000]);

        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);

        $rowSelector = $this->getIndexEntityRowSelector($entity->getId());
        static::assertSelectorTextSame($rowSelector.' td[data-column="price"]', '1.234,56');
        static::assertSelectorTextSame($rowSelector.' td[data-column="quantity"]', '5.000');
    }
}
