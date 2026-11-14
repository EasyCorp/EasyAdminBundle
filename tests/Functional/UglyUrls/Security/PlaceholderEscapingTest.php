<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UglyUrls\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Kernel;

/**
 * In legacy URLs the entity ID is a query parameter, so the value of the
 * %entity_id% and %entity_short_id% placeholders must come from the loaded entity.
 *
 * @group legacy
 */
class PlaceholderEscapingTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testEntityIdOfTheDefaultPageTitleComesFromTheEntity(): void
    {
        // SQLite casts '1<...>' to the integer 1 when looking for the entity, so the page is
        // displayed for an ID that is not the canonical one; other databases may reject that ID
        if (!str_contains(strtolower($this->entityManager->getConnection()->getDatabasePlatform()::class), 'sqlite')) {
            $this->markTestSkipped('Only SQLite finds entities with IDs like "1<...>".');
        }

        $crawler = $this->client->request('GET', $this->generateDetailUrl('1<i>'));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title i'));
        static::assertSame('(#1)', $crawler->filter('h1.title small')->text());
    }
}
