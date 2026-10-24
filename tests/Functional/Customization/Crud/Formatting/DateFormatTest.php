<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\DateFormatTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setDateFormat() configuration method.
 */
class DateFormatTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DateFormatTestCrudController::class;
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

    public function testDateFormatConfiguration(): void
    {
        $entity = BlogPostFactory::createOne([
            'createdAt' => new \DateTime('2024-01-02 10:00:00'),
            'publishDate' => new \DateTime('2024-06-15'),
        ]);

        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);

        // the 'long' format shows the full month name followed by day and year
        $rowSelector = $this->getIndexEntityRowSelector($entity->getId());
        static::assertSelectorTextSame($rowSelector.' td[data-column="publishDate"]', 'June 15, 2024');
        static::assertSelectorTextSame($rowSelector.' td[data-column="createdAt"]', 'January 2, 2024');
    }
}
