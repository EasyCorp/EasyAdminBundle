<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\CustomHtmlAttributeTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

class AddCustomHtmlAttributeDashboardControllerTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected EntityRepository $categories;

    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return CustomHtmlAttributeTestDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    public function testSingleCustomAttribute(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertCount(1, $crawler->filter('a[test-attribute="test"]'));
    }

    public function testMultipleCustomAttribute(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertCount(1, $crawler->filter('a[multi-test-one="test1"]'));
        static::assertCount(1, $crawler->filter('a[multi-test-two="test2"]'));
        static::assertCount(1, $crawler->filter('span[badge-attr="badge1"]'));
    }
}
