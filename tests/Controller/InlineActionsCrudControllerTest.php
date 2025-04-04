<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\InlineActionsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;

class InlineActionsCrudControllerTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

    protected function getControllerFqcn(): string
    {
        return InlineActionsCrudController::class;
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

    public function testCssClasses(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // TODO : see how to test the presence of the form into the inline actions
    }

    public function testFormAction(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // use of 20 as there will be 1 on each of the line => by default 20 as defined in EasyAdminBundle\Config\Crud
        $nbEntitiesPerPage = 20;
        static::assertCount($nbEntitiesPerPage, $crawler->filter('form[id^="form-action_form_entity-"]'));
        static::assertCount($nbEntitiesPerPage, $crawler->filter('form[id^="form-action_form_entity-"] > button'));
        static::assertSame('POST', $crawler->filter('form[id^="form-action_form_entity-"]')->attr('method'));
    }
}
