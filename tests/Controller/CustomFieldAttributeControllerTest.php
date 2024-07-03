<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CustomFieldAttributeCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;

class CustomFieldAttributeControllerTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

    protected function getControllerFqcn(): string
    {
        return CustomFieldAttributeCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecureDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    public function testItAddsAttributesToTd(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertCount(20, $crawler->filter('td[multi-test-one="test1"]'));
        static::assertCount(20, $crawler->filter('td[multi-test-two="test2"]'));
    }
}
