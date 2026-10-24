<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Display;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Display\HideNullValuesTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\BlogPostFactory;
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
        $entityWithRating = BlogPostFactory::createOne(['rating' => '4.75']);
        $entityWithoutRating = BlogPostFactory::createOne(['rating' => null]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(2);

        static::assertSelectorTextSame($this->getIndexEntityRowSelector($entityWithRating->getId()).' td[data-column="rating"]', '4.75');

        // with hideNullValues() the cell of a null value is empty instead of showing the "Null" label
        $nullRatingCell = $crawler->filter($this->getIndexEntityRowSelector($entityWithoutRating->getId()).' td[data-column="rating"]');
        static::assertCount(1, $nullRatingCell);
        static::assertSame('', trim($nullRatingCell->text()));
    }
}
