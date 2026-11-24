<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\Formatting;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\Formatting\TimezoneTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory\BlogPostFactory;
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
        $entity = BlogPostFactory::createOne(['createdAt' => new \DateTime('2024-01-02 10:00:00', new \DateTimeZone('UTC'))]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexPageEntityCount(1);

        // 10:00 UTC is displayed as 5:00 in America/New_York (UTC-5 in January)
        $dateTimeText = trim($crawler->filter($this->getIndexEntityRowSelector($entity->getId()).' td[data-column="createdAt"]')->text());
        static::assertMatchesRegularExpression('/^Jan 2, 2024, 5:00:00\h?AM$/u', $dateTimeText);
    }

    public function testFormDisplaysAndReadsDatesInTheConfiguredTimezone(): void
    {
        $entity = BlogPostFactory::createOne(['createdAt' => new \DateTime('2024-01-02 10:00:00', new \DateTimeZone('UTC'))]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // 10:00 UTC is 5:00 in America/New_York (UTC-5 in January)
        static::assertSame('2024-01-02T05:00', $crawler->filter('#BlogPost_createdAt')->attr('value'));

        $this->client->submitForm('Save changes', ['BlogPost[createdAt]' => '2024-01-02T07:00']);

        $this->entityManager->clear();
        $savedEntity = $this->entityManager->find(BlogPost::class, $entity->getId());
        static::assertSame((new \DateTime('2024-01-02 12:00:00', new \DateTimeZone('UTC')))->getTimestamp(), $savedEntity->getCreatedAt()->getTimestamp());
    }

    public function testFiltersReadDatesInTheConfiguredTimezone(): void
    {
        BlogPostFactory::createOne(['title' => 'Entity 1', 'createdAt' => new \DateTime('2024-01-02 10:00:00', new \DateTimeZone('UTC'))]);
        BlogPostFactory::createOne(['title' => 'Entity 2', 'createdAt' => new \DateTime('2024-01-02 12:00:00', new \DateTimeZone('UTC'))]);

        // 6:00 in America/New_York is 11:00 UTC
        $crawler = $this->client->request('GET', $this->getCrudUrl('index', null, [
            'filters[createdAt][comparison]' => '>',
            'filters[createdAt][value]' => '2024-01-02T06:00',
        ]));

        static::assertResponseIsSuccessful();
        $this->assertIndexFullEntityCount(1);
        static::assertStringContainsString('Entity 2', $crawler->filter('td[data-column="title"]')->text());
    }

    public function testDateAndTimeValuesAreNotConverted(): void
    {
        $entity = BlogPostFactory::createOne(['title' => 'Entity 1', 'publishDate' => new \DateTime('2024-01-02'), 'publishTime' => new \DateTime('10:00:00')]);
        BlogPostFactory::createOne(['title' => 'Entity 2', 'publishDate' => new \DateTime('2024-01-03'), 'publishTime' => new \DateTime('11:00:00')]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        static::assertSame('2024-01-02', $crawler->filter('#BlogPost_publishDate')->attr('value'));
        static::assertSame('10:00', $crawler->filter('#BlogPost_publishTime')->attr('value'));

        $crawler = $this->client->request('GET', $this->getCrudUrl('index', null, [
            'filters[publishDate][comparison]' => '=',
            'filters[publishDate][value]' => '2024-01-02',
            'filters[publishTime][comparison]' => '=',
            'filters[publishTime][value]' => '10:00',
        ]));

        static::assertResponseIsSuccessful();
        $this->assertIndexFullEntityCount(1);
        static::assertStringContainsString('Entity 1', $crawler->filter('td[data-column="title"]')->text());
    }
}
