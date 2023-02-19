<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Config\Action as AppAction;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class CategoryCrudControllerTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected AdminUrlGenerator $adminUrlGenerator;
    protected EntityManagerInterface $entityManager;
    protected EntityRepository $categories;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $container = static::getContainer();
        $this->adminUrlGenerator = $container->get(AdminUrlGenerator::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    /**
     * @dataProvider new
     */
    public function testNew(?string $invalidCsrfToken, ?string $expectedErrorMessage)
    {
        $this->client->request('GET', $this->generateNewCategoryFormUrl());

        $form = [
            'Category[name]' => 'Foo',
            'Category[slug]' => 'foo',
        ];
        if (null !== $invalidCsrfToken) {
            $form['Category[_token]'] = $invalidCsrfToken;
        }

        $this->client->submitForm('Create', $form);
        if (null === $expectedErrorMessage) {
            $this->assertSelectorNotExists('.global-invalid-feedback');
            $this->assertInstanceOf(Category::class, $this->categories->findOneBy(['slug' => 'foo']));
        } else {
            $this->assertSelectorTextContains('.global-invalid-feedback', $expectedErrorMessage);
            $this->assertNull($this->categories->findOneBy(['slug' => 'foo']));
        }
    }

    public static function new(): \Generator
    {
        yield [
            '', // Manipulate the CSRF token to an empty value
            'The CSRF token is invalid.',
        ];
        yield [
            '123abc', // Manipulate the CSRF token to this invalid value
            'The CSRF token is invalid.',
        ];
        yield [
            null, // Do not manipulate the CSRF token
            null, // Do not expect any error
        ];
    }

    /**
     * @dataProvider edit
     */
    public function testEdit(?string $invalidCsrfToken, ?string $expectedErrorMessage)
    {
        $this->client->request('GET', $this->generateEditCategoryFormUrl($this->categories->findOneBy([])->getId()));

        $form = [
            'Category[name]' => 'Bar',
            'Category[slug]' => 'bar',
        ];
        if (null !== $invalidCsrfToken) {
            $form['Category[_token]'] = $invalidCsrfToken;
        }

        $this->client->submitForm('Save changes', $form);
        if (null === $expectedErrorMessage) {
            $this->assertSelectorNotExists('.global-invalid-feedback');
            $this->assertInstanceOf(Category::class, $this->categories->findOneBy(['slug' => 'bar']));
        } else {
            $this->assertSelectorTextContains('.global-invalid-feedback', $expectedErrorMessage);
            $this->assertNull($this->categories->findOneBy(['slug' => 'bar']));
        }
    }

    public static function edit(): \Generator
    {
        yield [
            '', // Manipulate the CSRF token to an empty value
            'The CSRF token is invalid.',
        ];
        yield [
            '123abc', // Manipulate the CSRF token to this invalid value
            'The CSRF token is invalid.',
        ];
        yield [
            null, // Do not manipulate the CSRF token
            null, // Do not expect any error
        ];
    }

    /**
     * @dataProvider delete
     */
    public function testDelete(?string $invalidCsrfToken, callable $expectedCategoriesCount)
    {
        $initialCategoriesCount = \count($this->categories->findAll());

        // List all categories
        $crawler = $this->client->request('GET', $this->generateCategoryIndexUrl());
        $this->assertResultCount($initialCategoriesCount);

        // Try to delete the first found category
        $form = $crawler->filter('#delete-form')->form();
        $form->getNode()->setAttribute(
            'action',
            $crawler->filter('a.action-delete')->first()->attr('formaction')
        );
        if (null !== $invalidCsrfToken) {
            $form['token'] = $invalidCsrfToken;
        }
        $this->client->submit($form);

        // List all categories again and see if the result count changed
        $this->client->request('GET', $this->generateCategoryIndexUrl());
        $this->assertResultCount($expectedCategoriesCount($initialCategoriesCount));
    }

    public static function delete(): \Generator
    {
        yield [
            '', // Manipulate the CSRF token to an empty value
            fn (int $initialCategoriesCount): int => $initialCategoriesCount,
        ];
        yield [
            '123abc', // Manipulate the CSRF token to this invalid value
            fn (int $initialCategoriesCount): int => $initialCategoriesCount,
        ];
        yield [
            null, // Do not manipulate the CSRF token
            fn (int $initialCategoriesCount): int => $initialCategoriesCount - 1,
        ];
    }

    public function testDetail()
    {
        /* @var Category $category */
        $category = $this->categories->findOneBy([]);

        $this->client->request('GET', $this->generateCategoryDetailUrl($category->getId()));

        $this->assertSelectorTextContains('.form-panel-body', $category->getId());
        $this->assertSelectorTextContains('.form-panel-body', $category->getName());
        $this->assertSelectorTextContains('.form-panel-body', $category->getSlug());
        $this->assertSelectorTextContains('.form-panel-body', true === $category->isActive() ? 'Yes' : 'No');
    }

    /**
     * @dataProvider toggle
     */
    public function testToggle(string $method, ?string $invalidCsrfToken, int $expectedStatusCode, bool $toggleIsExpectedToSucceed)
    {
        if (Response::HTTP_METHOD_NOT_ALLOWED === $expectedStatusCode) {
            // needed to not display 'Uncaught PHP exception' messages in PHPUnit output
            // see https://stackoverflow.com/questions/50456114/phpunit-dont-report-symfony-exceptions-rendered-to-http-errors/50465691
            $this->expectException(MethodNotAllowedHttpException::class);
            $this->client->catchExceptions(false);
        }

        // Find the first toggle URL in the category list
        $crawler = $this->client->request('GET', $this->generateCategoryIndexUrl());
        $firstFoundToggleUrl = $crawler->filter('td.field-boolean .form-switch input[type="checkbox"]')->first()->attr('data-toggle-url');

        // Get the category's active state from the DB
        parse_str(parse_url($firstFoundToggleUrl, \PHP_URL_QUERY), $parameters);
        $categoryId = $parameters['entityId'];
        $active = $this->categories->find($categoryId)->isActive();
        $this->assertIsBool($active);

        // Adapt the toggle URL, so it will change the category's active property to the opposite of what it is currently
        $firstFoundToggleUrl .= sprintf('&newValue=%s', false === $active ? 'true' : 'false');

        // Change the CSRF token
        if (null !== $invalidCsrfToken) {
            $firstFoundToggleUrl = preg_replace('/csrfToken=.+?&/', sprintf('csrfToken=%s&', $invalidCsrfToken), $firstFoundToggleUrl);
        }

        // Do the AJAX request
        $this->client->request($method, $firstFoundToggleUrl, [], [], [
            'HTTP_x-requested-with' => 'XMLHttpRequest',
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => '1234',
        ]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
        /* @var Category $category */
        $this->entityManager->refresh($category = $this->categories->find($categoryId)); // After the request refresh the category
        $this->assertIsBool($category->isActive());
        if (true === $toggleIsExpectedToSucceed) {
            $this->assertNotSame($active, $category->isActive());
        } else {
            $this->assertSame($active, $category->isActive());
        }
    }

    public function testPagination()
    {
        $crawler = $this->client->request('GET', $this->generateCategoryIndexUrl());

        $prevPageItem = $crawler->filter('.list-pagination-paginator .page-item:nth-child(1)');
        $prevPageLink = $prevPageItem->filter('.page-link');
        $firstPageItem = $crawler->filter('.list-pagination-paginator .page-item:nth-child(2)');
        $firstPageLink = $firstPageItem->filter('.page-link');
        $secondPageLink = $crawler->filter('.list-pagination-paginator .page-item:nth-child(3) .page-link');
        $nextPageItem = $crawler->filter('.list-pagination-paginator .page-item:nth-child(4)');
        $nextPageLink = $nextPageItem->filter('.page-link');

        // test default pagination items
        $this->assertCount(4, $crawler->filter('.list-pagination-paginator .page-item'));
        $this->assertStringContainsString('Previous', $prevPageLink->text());
        $this->assertStringContainsString('disabled', $prevPageItem->attr('class'));
        $this->assertStringContainsString('1', $firstPageLink->text());
        $this->assertStringContainsString('active', $firstPageItem->attr('class'));
        $this->assertStringContainsString('2', $secondPageLink->text());
        $this->assertStringContainsString('Next', $nextPageLink->text());

        // test default pagination URLs
        $firstPageUrl = $firstPageLink->attr('href');
        $this->assertSame('1', $this->getParameterFromUrlQueryString($firstPageUrl, EA::PAGE));

        $secondPageUrl = $secondPageLink->attr('href');
        $this->assertSame('2', $this->getParameterFromUrlQueryString($secondPageUrl, EA::PAGE));

        $nextPageUrl = $nextPageLink->attr('href');
        $this->assertSame($secondPageUrl, $nextPageUrl);

        // test pagination maintains all query parameters, including custom ones
        $queryParameters = http_build_query(['sort[name]' => 'DESC', 'CUSTOM_param' => 'foobar1234']);
        $crawler = $this->client->request('GET', $this->generateCategoryIndexUrl().'&'.$queryParameters);

        $firstPageUrl = $crawler->filter('.list-pagination-paginator .page-item:nth-child(2) .page-link')->attr('href');
        $this->assertSame(['name' => 'DESC'], $this->getParameterFromUrlQueryString($firstPageUrl, 'sort'));
        $this->assertSame('foobar1234', $this->getParameterFromUrlQueryString($firstPageUrl, 'CUSTOM_param'));

        $nextPagePageUrl = $crawler->filter('.list-pagination-paginator .page-item:nth-child(4) .page-link')->attr('href');
        $this->assertSame(['name' => 'DESC'], $this->getParameterFromUrlQueryString($nextPagePageUrl, 'sort'));
        $this->assertSame('foobar1234', $this->getParameterFromUrlQueryString($nextPagePageUrl, 'CUSTOM_param'));
    }

    public static function toggle(): \Generator
    {
        yield [
            'GET', // HTTP method
            null, // Do not manipulate the CSRF token
            Response::HTTP_METHOD_NOT_ALLOWED, // Response status code, fails because of wrong method "GET"
            false, // Should the toggle successfully change the toggled property?
        ];
        yield [
            'PATCH',
            '123abc', // Manipulate the CSRF token to this invalid value
            Response::HTTP_UNAUTHORIZED, // Response status code, fails because of wrong CSRF token
            false,
        ];
        yield [
            'PATCH',
            null, // Do not manipulate the CSRF token
            Response::HTTP_OK,
            true,
        ];
    }

    /**
     * @dataProvider search
     */
    public function testSearch(array $categories, string $query, int $expectedResultCount)
    {
        foreach ($categories as $category) {
            $this->entityManager->persist($category);
        }
        $this->entityManager->flush();

        $this->client->request('GET', $this->generateCategoryIndexUrl($query));
        $this->assertResultCount($expectedResultCount);
    }

    public static function search(): \Generator
    {
        yield [
            [],
            'foobazfoobar',
            0,
        ];
        yield [
            [
                (new Category())->setName('Foobaz')->setSlug('foobaz'),
            ],
            'foobaz',
            1,
        ];
        yield [
            [
                (new Category())->setName('Bazbar')->setSlug('bazbar'),
                (new Category())->setName('Bazbar 2')->setSlug('bazbar-2'),
            ],
            'bazbar',
            2,
        ];
    }

    /**
     * @dataProvider filter
     */
    public function testFilter(array $categories, array $filters, int $expectedResultCount)
    {
        foreach ($categories as $category) {
            $this->entityManager->persist($category);
        }
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateCategoryFilterFormUrl(), [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $form = $crawler->filter('form[name="filters"]')->form();
        $form['filters'] = $filters;
        $this->client->submit($form, [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        $this->assertResultCount($expectedResultCount);
    }

    public static function filter(): \Generator
    {
        yield [
            [],
            [
                'name' => [
                    'comparison' => 'like',
                    'value' => 'foobazfoobar',
                ],
            ],
            0,
        ];
        yield [
            [
                (new Category())->setName('Buzzbar')->setSlug('buzzbar'),
            ],
            [
                'name' => [
                    'comparison' => 'like',
                    'value' => 'buzzb',
                ],
            ],
            1,
        ];
        yield [
            [
                (new Category())->setName('Buzzfoo')->setSlug('buzzfoo')->setActive(true),
                (new Category())->setName('Buzzfoo 2')->setSlug('buzzfoo-2')->setActive(true),
                (new Category())->setName('Buzzfoo 3')->setSlug('buzzfoo-3')->setActive(false),
            ],
            [
                'name' => [
                    'comparison' => 'like',
                    'value' => 'zzfoo',
                ],
                'active' => '1',
            ],
            2,
        ];
    }

    /**
     * @dataProvider customPage
     */
    public function testCustomPage(string $username, int $expectedStatusCode)
    {
        if (Response::HTTP_FORBIDDEN === $expectedStatusCode) {
            // needed to not display 'Uncaught PHP exception' messages in PHPUnit output
            // see https://stackoverflow.com/questions/50456114/phpunit-dont-report-symfony-exceptions-rendered-to-http-errors/50465691
            $this->expectException(ForbiddenActionException::class);
            $this->client->catchExceptions(false);
        }

        $this->client->request('GET', $this->generateCustomActionUrl(), [], [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function customPage(): \Generator
    {
        yield [self::generateUsername('ROLE_USER'), Response::HTTP_FORBIDDEN];
        yield [self::generateUsername('ROLE_ADMIN'), Response::HTTP_OK];
    }

    private function assertResultCount(int $expectedResultCount): void
    {
        if (0 > $expectedResultCount) {
            throw new \InvalidArgumentException();
        }

        if (0 === $expectedResultCount) {
            $this->assertSelectorTextSame('.no-results', 'No results found.');
        } else {
            $this->assertSelectorTextSame('.list-pagination-counter strong', (string) $expectedResultCount);
        }
    }

    private function generateCategoryIndexUrl(string $query = null): string
    {
        $adminUrlGenerator = $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::INDEX);

        if (null !== $query) {
            $adminUrlGenerator->set('query', $query);
        }

        return $adminUrlGenerator->generateUrl();
    }

    private function generateNewCategoryFormUrl(): string
    {
        return $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::NEW)
            ->generateUrl();
    }

    private function generateEditCategoryFormUrl(string|int $id): string
    {
        return $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::EDIT)
            ->setEntityId($id)
            ->generateUrl();
    }

    private function generateCategoryDetailUrl(string|int $id): string
    {
        return $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($id)
            ->generateUrl();
    }

    private function generateCategoryFilterFormUrl(): string
    {
        // Use the index URL as referrer but remove scheme, host and port
        $referrer = preg_replace('/^.*(\/.*)$/', '$1', $this->generateCategoryIndexUrl());

        return $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction('renderFilters')
            ->set('referrer', $referrer)
            ->generateUrl();
    }

    private function generateCustomActionUrl(): string
    {
        return $this->adminUrlGenerator
            ->setDashboard(SecureDashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(AppAction::CUSTOM_ACTION)
            ->generateUrl();
    }

    private static function generateUsername(string $role): string
    {
        switch ($role) {
            case 'ROLE_USER':
                return 'user';
            case 'ROLE_ADMIN':
                return 'admin';
        }

        throw new \InvalidArgumentException(sprintf('Unknown role, use one of: %s', implode(', ', ['ROLE_USER', 'ROLE_ADMIN'])));
    }

    private function getParameterFromUrlQueryString(string $url, string $parameterName): string|array|null
    {
        $queryString = parse_url($url, \PHP_URL_QUERY);
        parse_str($queryString, $queryStringParams);

        return $queryStringParams[$parameterName] ?? null;
    }
}
