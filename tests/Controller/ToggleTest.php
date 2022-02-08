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

class ToggleTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected AdminUrlGenerator $adminUrlGenerator;
    protected EntityManagerInterface $entityManager;
    protected EntityRepository $categories;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();

        $container = static::getContainer();
        $this->adminUrlGenerator = $container->get(AdminUrlGenerator::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    /**
     * @dataProvider toggle
     */
    public function testToggle(string $method, ?string $invalidCsrfToken, int $expectedStatusCode, bool $toggleIsExpectedToWork)
    {
        // Find the first toggle URL in the category list
        $indexUrl = $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        $crawler = $this->client->request('GET', $indexUrl, [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $firstFoundToggleUrl = $crawler->filter('td.field-boolean .form-switch input[type="checkbox"]')->first()->attr('data-toggle-url');

        // Get the category from the DB
        parse_str(parse_url($firstFoundToggleUrl, \PHP_URL_QUERY), $parameters);
        $entityId = $parameters['entityId'];

        // Adapt the toggle URL, so it will change the category's active property to the opposite of what it is currently
        $this->assertIsBool($active = $this->categories->find($entityId)->isActive());
        $firstFoundToggleUrl .= sprintf('&newValue=%s', false === $active ? 'true' : 'false');
        if (null !== $invalidCsrfToken) {
            $firstFoundToggleUrl = preg_replace('/csrfToken=.+?&/', sprintf('csrfToken=%s&', $invalidCsrfToken), $firstFoundToggleUrl);
        }

        $this->client->request($method, $firstFoundToggleUrl, [], [], [
            'HTTP_x-requested-with' => 'XMLHttpRequest',
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => '1234',
        ]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
        /* @var Category $category */
        $this->entityManager->refresh($category = $this->categories->find($entityId)); // After the request refresh the category
        $this->assertIsBool($category->isActive());
        if (true === $toggleIsExpectedToWork) {
            $this->assertNotSame($active, $category->isActive());
        } else {
            $this->assertSame($active, $category->isActive());
        }
    }

    public function toggle(): \Generator
    {
        yield [
            'GET', // HTTP method
            null, // Do not manipulate the CSRF token
            400, // Response status code
            false, // Should the toggle successfully change the toggled property?
        ];
        yield [
            'PATCH',
            '123abc', // Manipulate the CSRF token to this invalid value
            400,
            false,
        ];
        yield [
            'PATCH',
            null, // Do not manipulate the CSRF token
            200,
            true,
        ];
    }
}
