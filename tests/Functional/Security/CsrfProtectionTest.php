<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Tests CSRF protection for CRUD operations.
 */
class CsrfProtectionTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

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
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    /**
     * @dataProvider provideNewFormCsrfTokens
     */
    public function testNewFormCsrfProtection(?string $invalidCsrfToken, ?string $expectedErrorMessage): void
    {
        $this->client->request('GET', $this->generateNewFormUrl());

        $form = [
            'Category[name]' => 'CsrfTestCategory',
            'Category[slug]' => 'csrf-test-category',
        ];
        if (null !== $invalidCsrfToken) {
            $form['Category[_token]'] = $invalidCsrfToken;
        }

        $this->client->submitForm('Create', $form);
        if (null === $expectedErrorMessage) {
            static::assertSelectorNotExists('.global-invalid-feedback');
            static::assertInstanceOf(Category::class, $this->categories->findOneBy(['slug' => 'csrf-test-category']));
        } else {
            $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
            static::assertSelectorTextContains('.global-invalid-feedback', $expectedErrorMessage);
            static::assertNull($this->categories->findOneBy(['slug' => 'csrf-test-category']));
        }
    }

    public static function provideNewFormCsrfTokens(): \Generator
    {
        yield 'empty CSRF token' => [
            '',
            'The CSRF token is invalid.',
        ];
        yield 'invalid CSRF token' => [
            '123abc',
            'The CSRF token is invalid.',
        ];
        yield 'valid CSRF token' => [
            null,
            null,
        ];
    }

    /**
     * @dataProvider provideEditFormCsrfTokens
     *
     * @group legacy
     */
    public function testEditFormCsrfProtection(?string $invalidCsrfToken, ?string $expectedErrorMessage): void
    {
        $this->client->request('GET', $this->generateEditFormUrl($this->categories->findOneBy([])->getId()));

        $form = [
            'Category[name]' => 'CsrfEditTest',
            'Category[slug]' => 'csrf-edit-test',
        ];
        if (null !== $invalidCsrfToken) {
            $form['Category[_token]'] = $invalidCsrfToken;
        }

        $this->client->submitForm('Save changes', $form);
        if (null === $expectedErrorMessage) {
            static::assertSelectorNotExists('.global-invalid-feedback');
            static::assertInstanceOf(Category::class, $this->categories->findOneBy(['slug' => 'csrf-edit-test']));
        } else {
            $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
            static::assertSelectorTextContains('.global-invalid-feedback', $expectedErrorMessage);
            static::assertNull($this->categories->findOneBy(['slug' => 'csrf-edit-test']));
        }
    }

    public static function provideEditFormCsrfTokens(): \Generator
    {
        yield 'empty CSRF token' => [
            '',
            'The CSRF token is invalid.',
        ];
        yield 'invalid CSRF token' => [
            '123abc',
            'The CSRF token is invalid.',
        ];
        yield 'valid CSRF token' => [
            null,
            null,
        ];
    }

    /**
     * @dataProvider provideDeleteCsrfTokens
     *
     * @group legacy
     */
    public function testDeleteCsrfProtection(?string $invalidCsrfToken, callable $expectedCategoriesCount): void
    {
        $initialCategoriesCount = \count($this->categories->findAll());

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        static::assertIndexFullEntityCount($initialCategoriesCount);

        $deleteForm = $crawler->filter('form.action-delete')->first();

        if ($deleteForm->count() > 0) {
            $form = $deleteForm->form();
            if (null !== $invalidCsrfToken) {
                $form['token'] = $invalidCsrfToken;
            }
            $this->client->submit($form);
        } else {
            // use the confirmation form approach
            $confirmationForm = $crawler->filter('#action-confirmation-form');
            static::assertGreaterThan(0, $confirmationForm->count(), 'Confirmation form should exist');

            // get the delete URL from the delete action link/button
            $deleteAction = $crawler->filter('.action-delete')->first();
            static::assertGreaterThan(0, $deleteAction->count(), 'Delete action should exist');

            $deleteUrl = $deleteAction->attr('formaction') ?? $deleteAction->attr('href');
            static::assertNotNull($deleteUrl, 'Delete action should have a URL');

            $token = null !== $invalidCsrfToken
                ? $invalidCsrfToken
                : $confirmationForm->filter('input[name="token"]')->attr('value');

            $this->client->request('POST', $deleteUrl, ['token' => $token]);
        }

        $this->client->request('GET', $this->generateIndexUrl());
        static::assertIndexFullEntityCount($expectedCategoriesCount($initialCategoriesCount));
    }

    public static function provideDeleteCsrfTokens(): \Generator
    {
        yield 'empty CSRF token' => [
            '',
            static fn (int $initialCategoriesCount): int => $initialCategoriesCount,
        ];
        yield 'invalid CSRF token' => [
            '123abc',
            static fn (int $initialCategoriesCount): int => $initialCategoriesCount,
        ];
        yield 'valid CSRF token' => [
            null,
            static fn (int $initialCategoriesCount): int => $initialCategoriesCount - 1,
        ];
    }

    /**
     * @dataProvider provideToggleCsrfTokens
     */
    public function testToggleCsrfProtection(string $method, ?string $invalidCsrfToken, int $expectedStatusCode, bool $toggleIsExpectedToSucceed): void
    {
        $expectedExceptionClass = match ($expectedStatusCode) {
            Response::HTTP_METHOD_NOT_ALLOWED => MethodNotAllowedHttpException::class,
            Response::HTTP_BAD_REQUEST => BadRequestHttpException::class,
            default => null,
        };

        if (null !== $expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->client->catchExceptions(false);
        }

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $firstFoundToggleUrl = $crawler->filter('td.field-boolean .form-switch input[type="checkbox"]')->first()->attr('data-toggle-url');

        parse_str(parse_url($firstFoundToggleUrl, \PHP_URL_QUERY), $parameters);
        // with pretty URLs, entityId is in the URL path (e.g., /admin/category/123/edit)
        // with legacy URLs, entityId is in the query string
        if (isset($parameters['entityId'])) {
            $categoryId = $parameters['entityId'];
        } else {
            // extract entityId from URL path for pretty URLs
            $urlPath = parse_url($firstFoundToggleUrl, \PHP_URL_PATH);
            preg_match('#/(\d+)/edit$#', $urlPath, $matches);
            $categoryId = $matches[1] ?? throw new \RuntimeException('Could not extract entityId from toggle URL: '.$firstFoundToggleUrl);
        }
        $active = $this->categories->find($categoryId)->isActive();
        static::assertIsBool($active);

        // append newValue parameter (use ? if no existing query string, & otherwise)
        $separator = str_contains($firstFoundToggleUrl, '?') ? '&' : '?';
        $firstFoundToggleUrl .= sprintf('%snewValue=%s', $separator, false === $active ? 'true' : 'false');

        if (null !== $invalidCsrfToken) {
            $firstFoundToggleUrl = preg_replace('/csrfToken=.+?&/', sprintf('csrfToken=%s&', $invalidCsrfToken), $firstFoundToggleUrl);
        }

        $this->client->request($method, $firstFoundToggleUrl, [], [], [
            'HTTP_x-requested-with' => 'XMLHttpRequest',
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => '1234',
        ]);
        static::assertResponseStatusCodeSame($expectedStatusCode);

        $this->entityManager->refresh($category = $this->categories->find($categoryId));
        static::assertIsBool($category->isActive());
        if (true === $toggleIsExpectedToSucceed) {
            static::assertNotSame($active, $category->isActive());
        } else {
            static::assertSame($active, $category->isActive());
        }
    }

    public static function provideToggleCsrfTokens(): \Generator
    {
        yield 'GET method not allowed' => [
            'GET',
            null,
            Response::HTTP_METHOD_NOT_ALLOWED,
            false,
        ];
        yield 'invalid CSRF token' => [
            'PATCH',
            '123abc',
            Response::HTTP_UNAUTHORIZED,
            false,
        ];
        yield 'valid CSRF token' => [
            'PATCH',
            null,
            Response::HTTP_OK,
            true,
        ];
    }
}
