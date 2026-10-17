<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Display;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Display\HideNullValuesTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\DemoEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::hideNullValues() configuration method.
 */
class HideNullValuesTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return HideNullValuesTestCrudController::class;
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

    public function testHideNullValuesConfiguration(): void
    {
        $entityWithPrice = DemoEntityFactory::createOne(['price' => '99.99']);
        $entityWithoutPrice = DemoEntityFactory::createOne(['price' => null]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(2);

        static::assertSelectorTextSame($this->getIndexEntityRowSelector($entityWithPrice->getId()).' td[data-column="price"]', '99.99');

        // with hideNullValues() the cell of a null value is empty instead of showing the "Null" label
        $nullPriceCell = $crawler->filter($this->getIndexEntityRowSelector($entityWithoutPrice->getId()).' td[data-column="price"]');
        static::assertCount(1, $nullPriceCell);
        static::assertSame('', trim($nullPriceCell->text()));
    }
}
