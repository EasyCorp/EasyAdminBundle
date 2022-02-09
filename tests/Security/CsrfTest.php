<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CsrfTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected AdminUrlGenerator $adminUrlGenerator;
    protected EntityRepository $categories;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();

        $container = static::getContainer();
        $this->adminUrlGenerator = $container->get(AdminUrlGenerator::class);
        $this->categories = $container->get(EntityManagerInterface::class)->getRepository(Category::class);
    }

    public function testNewWithInvalidCsrfToken()
    {
        $newForm = $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::NEW)
            ->generateUrl();
        $this->client->request('GET', $newForm, [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->client->submitForm('Create', [
            'Category[name]' => 'Foo',
            'Category[slug]' => 'foo',
            'Category[_token]' => null, // Provide no CSRF token
        ]);
        $this->assertSelectorTextContains('.global-invalid-feedback', 'The CSRF token is invalid.');

        $this->client->submitForm('Create', [
            'Category[name]' => 'Foo',
            'Category[slug]' => 'foo',
            'Category[_token]' => '123', // Provide an invalid CSRF token
        ]);
        $this->assertSelectorTextContains('.global-invalid-feedback', 'The CSRF token is invalid.');
    }

    public function testEditWithInvalidCsrfToken()
    {
        $editForm = $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::EDIT)
            ->setEntityId($this->categories->findOneBy([])->getId())
            ->generateUrl();
        $this->client->request('GET', $editForm, [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->client->submitForm('Save changes', [
            'Category[name]' => 'Foo',
            'Category[slug]' => 'foo',
            'Category[_token]' => null, // Provide no CSRF token
        ]);
        $this->assertSelectorTextContains('.global-invalid-feedback', 'The CSRF token is invalid.');

        $this->client->submitForm('Save changes', [
            'Category[name]' => 'Foo',
            'Category[slug]' => 'foo',
            'Category[_token]' => '123', // Provide an invalid CSRF token
        ]);
        $this->assertSelectorTextContains('.global-invalid-feedback', 'The CSRF token is invalid.');
    }

    public function testDeleteWithInvalidCsrfToken()
    {
        $categoriesCount = \count($this->categories->findAll());

        // List all categories
        $indexUrl = $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        $crawler = $this->client->request('GET', $indexUrl, [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $this->assertSelectorTextSame('.list-pagination-counter strong', (string) $categoriesCount);

        // Try to delete the first found category
        $form = $crawler->filter('#delete-form')->form();
        $form->getNode()->setAttribute(
            'action',
            $crawler->filter('a.action-delete')->first()->attr('formaction')
        );
        $form['token'] = null; // Provide no CSRF token
        $this->client->submit($form);

        // List all categories and see if the result count changed
        $this->client->request('GET', $indexUrl, [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $this->assertSelectorTextSame('.list-pagination-counter strong', (string) $categoriesCount);

        // Try to delete the first found category
        $form = $crawler->filter('#delete-form')->form();
        $form->getNode()->setAttribute(
            'action',
            $crawler->filter('a.action-delete')->first()->attr('formaction')
        );
        $form['token'] = '123';  // Provide an invalid CSRF token
        $this->client->submit($form);

        // List all categories and see if the result count changed
        $this->client->request('GET', $indexUrl, [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $this->assertSelectorTextSame('.list-pagination-counter strong', (string) $categoriesCount);
    }
}
