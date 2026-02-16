<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests role-based permission checks for CRUD actions.
 */
class RolePermissionTest extends AbstractCrudTestCase
{
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
    }

    /**
     * @dataProvider provideRolesForAdminOnlyAction
     */
    public function testAdminOnlyActionPermission(string $username, int $expectedStatusCode): void
    {
        if (Response::HTTP_FORBIDDEN === $expectedStatusCode) {
            $this->expectException(ForbiddenActionException::class);
            $this->client->catchExceptions(false);
        }

        $this->client->request(
            'GET',
            $this->getCrudUrl(CategoryCrudController::ACTION_ADMIN_ONLY),
            [],
            [],
            ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']
        );

        static::assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideRolesForAdminOnlyAction(): \Generator
    {
        yield 'user role cannot access admin-only action' => ['user', Response::HTTP_FORBIDDEN];
        yield 'admin role can access admin-only action' => ['admin', Response::HTTP_OK];
        yield 'super_admin role can access admin-only action' => ['super_admin', Response::HTTP_OK];
    }

    public function testUnauthenticatedUserCannotAccessProtectedDashboard(): void
    {
        // securedApp has access_control requiring ROLE_USER for /admin
        $this->client->request('GET', '/admin');

        // should get 401 Unauthorized (HTTP Basic auth challenge)
        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAuthenticatedUserCanAccessDashboard(): void
    {
        $this->client->request(
            'GET',
            '/admin',
            [],
            [],
            ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']
        );

        static::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider provideRolesForFieldPermission
     */
    public function testFieldPermissionHidesFieldForUnauthorizedUsers(string $username, bool $shouldSeeField): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateIndexUrl(),
            [],
            [],
            ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']
        );

        $fieldHeader = $crawler->filter('th[data-column="activeWithNoPermission"]');

        if ($shouldSeeField) {
            static::assertCount(1, $fieldHeader, 'Super admin should see the field with ROLE_SUPER_ADMIN permission');
        } else {
            static::assertCount(0, $fieldHeader, 'User without ROLE_SUPER_ADMIN should not see the protected field');
        }
    }

    public static function provideRolesForFieldPermission(): \Generator
    {
        yield 'user cannot see protected field' => ['user', false];
        yield 'admin cannot see protected field' => ['admin', false];
        yield 'super_admin can see protected field' => ['super_admin', true];
    }
}
