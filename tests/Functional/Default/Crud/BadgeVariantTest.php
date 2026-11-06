<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Crud;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Crud\BadgeVariantCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Dashboard\ComponentDefaultsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The badge variant of a field takes precedence over the one configured in the
 * dashboard ('dot'), which still applies to the fields that don't set their own.
 *
 * Anchors the ChoiceField rule of skills/easyadmin/SKILL.md about setBadgeVariant().
 * If this behavior changes, update that skill rule too.
 */
class BadgeVariantTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return BadgeVariantCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return ComponentDefaultsDashboardController::class;
    }

    public function testIndexPage(): void
    {
        CategoryFactory::createOne(['active' => true, 'activeDisabled' => true, 'priority' => 2]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertBadgeVariants($crawler->filter('.datagrid tbody tr')->first());
    }

    public function testDetailPage(): void
    {
        $category = CategoryFactory::createOne(['active' => true, 'activeDisabled' => true, 'priority' => 2]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($category->getId()));

        static::assertResponseIsSuccessful();
        $this->assertBadgeVariants($crawler);
    }

    private function assertBadgeVariants(Crawler $crawler): void
    {
        $this->assertSame('badge badge-boolean-true badge-variant-dot', $crawler->filter('.field-boolean')->eq(0)->filter('.badge')->attr('class'));
        $this->assertSame('badge badge-boolean-true badge-variant-outline', $crawler->filter('.field-boolean')->eq(1)->filter('.badge')->attr('class'));
        $this->assertSame('badge badge-danger badge-variant-solid', $crawler->filter('.field-select .badge')->attr('class'));
    }
}
