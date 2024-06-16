<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\FormFieldsetsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class FormFieldsetsCrudControllerTest extends AbstractCrudTestCase
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
        return FormFieldsetsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsWithoutFieldsetAreAssignedAnAutomaticFieldsetInForms()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        // the 'id' field does not belong to any explicit fieldset, so EasyAdmin creates a new fieldset for it
        static::assertSame('BlogPost[id]', $crawler->filter('form.ea-new-form .form-fieldset')->first()->filter('input')->attr('name'));

        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($blogPost->getId()));
        // the 'id' field does not belong to any explicit fieldset, so EasyAdmin creates a new fieldset for it
        static::assertSame('BlogPost[id]', $crawler->filter('form.ea-edit-form .form-fieldset')->first()->filter('input')->attr('name'));
    }

    public function testFieldsWithoutFieldsetAreAssignedAnAutomaticFieldsetInDetailPage()
    {
        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertSame('ID', $crawler->filter('.content-body .field-group')->first()->filter('.field-label')->text());
        // static::assertSame('ID', trim($crawler->filter('.form-fieldset')->first()->filter('dt')->text()));
    }

    public function testFieldsInsideFieldsetsInForms()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1") input'));
        static::assertSame('BlogPost[title]', trim($crawler->filter('.form-fieldset:contains("Fieldset 1") input')->attr('name')));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-info.form-fieldset:contains("Fieldset 1")'));
        static::assertStringContainsString('fa fa-cog', $crawler->filter('.form-fieldset:contains("Fieldset 1") .form-fieldset-title i')->attr('class'));

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2") input'));
        static::assertSame('BlogPost[slug]', trim($crawler->filter('.form-fieldset:contains("Fieldset 2") input')->attr('name')));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-warning.form-fieldset:contains("Fieldset 2")'));
        static::assertStringContainsString('fa fa-user', $crawler->filter('.form-fieldset:contains("Fieldset 2") .form-fieldset-title i')->attr('class'));
    }

    public function testFieldsInsideFieldsetsInDetailPage()
    {
        $blogPost = $this->blogPosts->findOneBy([]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 1") .field-group'));
        static::assertSame('Title', trim($crawler->filter('.form-fieldset:contains("Fieldset 1") .field-group .field-label')->text()));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-info.form-fieldset:contains("Fieldset 1")'));
        static::assertStringContainsString('fa fa-cog', $crawler->filter('.form-fieldset:contains("Fieldset 1") .form-fieldset-title i')->attr('class'));

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2")'));
        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 2") .field-group'));
        static::assertSame('Slug', trim($crawler->filter('.form-fieldset:contains("Fieldset 2") .field-group .field-label')->text()));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-warning.form-fieldset:contains("Fieldset 2")'));
        static::assertStringContainsString('fa fa-user', $crawler->filter('.form-fieldset:contains("Fieldset 2") .form-fieldset-title i')->attr('class'));
    }

    public function testFieldsetWithoutFieldsInForms()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 3")'));
        static::assertCount(0, $crawler->filter('.form-fieldset:contains("Fieldset 3") input'));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-danger.form-fieldset:contains("Fieldset 3")'));
        static::assertStringContainsString('fa fa-file-alt', $crawler->filter('.form-fieldset:contains("Fieldset 3") .form-fieldset-title i')->attr('class'));
    }

    public function testFieldsetWithoutFieldsInDetailPage()
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertCount(1, $crawler->filter('.form-fieldset:contains("Fieldset 3")'));
        static::assertCount(0, $crawler->filter('.form-fieldset:contains("Fieldset 3") dt'));
        static::assertCount(1, $crawler->filter('.field-form_fieldset.bg-danger.form-fieldset:contains("Fieldset 3")'));
        static::assertStringContainsString('fa fa-file-alt', $crawler->filter('.form-fieldset:contains("Fieldset 3") .form-fieldset-title i')->attr('class'));
    }
}
