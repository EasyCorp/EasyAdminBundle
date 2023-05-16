<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\FieldPanelsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class FieldPanelsCrudControllerTest extends AbstractCrudTestCase
{
    protected EntityRepository $blogPosts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->blogPosts = $this->entityManager->getRepository(BlogPost::class);
    }

    protected function getControllerFqcn(): string
    {
        return FieldPanelsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsWithoutPanelAreAssignedAnAutomaticPanelInForms()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        // the 'id' field does not belong to any explicit panel, so EasyAdmin creates a new panel for it
        static::assertSame('BlogPost[id]', $crawler->filter('form.ea-new-form .form-panel')->first()->filter('input')->attr('name'));

        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($blogPost->getId()));
        // the 'id' field does not belong to any explicit panel, so EasyAdmin creates a new panel for it
        static::assertSame('BlogPost[id]', $crawler->filter('form.ea-edit-form .form-panel')->first()->filter('input')->attr('name'));
    }

    public function testFieldsWithoutPanelAreAssignedAnAutomaticPanelInDetailPage()
    {
        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertSame('ID', trim($crawler->filter('.form-panel')->first()->filter('dt')->text()));
    }

    public function testFieldsInsidePanelsInForms()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 1")'));
        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 1") input'));
        static::assertSame('BlogPost[title]', trim($crawler->filter('.form-panel:contains("Panel 1") input')->attr('name')));
        static::assertCount(1, $crawler->filter('.field-form_panel.bg-info .form-panel:contains("Panel 1")'));
        static::assertStringContainsString('fa fa-cog', $crawler->filter('.form-panel:contains("Panel 1") .form-panel-title i')->attr('class'));

        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 2")'));
        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 2") input'));
        static::assertSame('BlogPost[slug]', trim($crawler->filter('.form-panel:contains("Panel 2") input')->attr('name')));
        static::assertCount(1, $crawler->filter('.field-form_panel.bg-warning .form-panel:contains("Panel 2")'));
        static::assertStringContainsString('fa fa-user', $crawler->filter('.form-panel:contains("Panel 2") .form-panel-title i')->attr('class'));
    }

    public function testFieldsInsidePanelsInDetailPage()
    {
        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 1")'));
        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 1") dt'));
        static::assertSame('Title', trim($crawler->filter('.form-panel:contains("Panel 1") dt')->text()));
        static::assertCount(1, $crawler->filter('.field-form_panel.bg-info .form-panel:contains("Panel 1")'));
        static::assertStringContainsString('fa fa-cog', $crawler->filter('.form-panel:contains("Panel 1") .form-panel-title i')->attr('class'));

        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 2")'));
        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 2") dt'));
        static::assertSame('Slug', trim($crawler->filter('.form-panel:contains("Panel 2") dt')->text()));
        static::assertCount(1, $crawler->filter('.field-form_panel.bg-warning .form-panel:contains("Panel 2")'));
        static::assertStringContainsString('fa fa-user', $crawler->filter('.form-panel:contains("Panel 2") .form-panel-title i')->attr('class'));
    }

    public function testPanelWithoutFieldsInForms()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 3")'));
        static::assertCount(0, $crawler->filter('.form-panel:contains("Panel 3") input'));
        static::assertCount(1, $crawler->filter('.field-form_panel.bg-danger .form-panel:contains("Panel 3")'));
        static::assertStringContainsString('fa fa-file-alt', $crawler->filter('.form-panel:contains("Panel 3") .form-panel-title i')->attr('class'));
    }

    public function testPanelWithoutFieldsInDetailPage()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-panel:contains("Panel 3")'));
        static::assertCount(0, $crawler->filter('.form-panel:contains("Panel 3") dt'));
        static::assertCount(1, $crawler->filter('.field-form_panel.bg-danger .form-panel:contains("Panel 3")'));
        static::assertStringContainsString('fa fa-file-alt', $crawler->filter('.form-panel:contains("Panel 3") .form-panel-title i')->attr('class'));
    }
}
