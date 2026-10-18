<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\TimezoneTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\DemoEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setTimezone() configuration method.
 */
class TimezoneTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return TimezoneTestCrudController::class;
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

    public function testTimezoneConfiguration(): void
    {
        $entity = DemoEntityFactory::createOne(['createdAt' => new \DateTime('2024-01-02 10:00:00', new \DateTimeZone('UTC'))]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);

        // 10:00 UTC is displayed as 5:00 in America/New_York (UTC-5 in January)
        $dateTimeText = trim($crawler->filter($this->getIndexEntityRowSelector($entity->getId()).' td[data-column="createdAt"]')->text());
        static::assertMatchesRegularExpression('/^Jan 2, 2024, 5:00:00\h?AM$/u', $dateTimeText);
    }
}
