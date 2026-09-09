<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecureRouteController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\HttpFoundation\Response;

/**
 * A custom action (Action::linkToRoute()/MenuItem::linkToRoute()) is dispatched by
 * AdminRouterSubscriber, which swaps the executed controller based on the '?routeName='
 * query parameter on the 'kernel.controller' event. That happens after Symfony's security
 * firewall has already evaluated 'access_control' against the original admin URL, so a
 * path-based rule protecting the target route used to be silently skipped, letting a
 * low-privilege backend user reach a more restricted route.
 *
 * SecuredApp restricts '^/admin/secure' to ROLE_ADMIN and '^/admin' to ROLE_USER.
 */
class RouteNameAccessControlBypassTest extends AbstractCrudTestCase
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

    /**
     * Control test: the access_control rule works as expected on direct access.
     */
    public function testProtectedRouteIsDeniedForUserOnDirectAccess(): void
    {
        $this->client->request('GET', '/admin/secure/danger-zone', [], [], ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']);

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertStringNotContainsString(SecureRouteController::SECRET, (string) $this->client->getResponse()->getContent());
    }

    public function testProtectedRouteIsAllowedForAdminOnDirectAccess(): void
    {
        $this->client->request('GET', '/admin/secure/danger-zone', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        static::assertResponseIsSuccessful();
        static::assertStringContainsString(SecureRouteController::SECRET, (string) $this->client->getResponse()->getContent());
    }

    /**
     * The bypass: reaching the ROLE_ADMIN route through '?routeName=' as a ROLE_USER must
     * be denied, exactly like direct access is.
     */
    public function testProtectedRouteCannotBeReachedThroughRouteNameByUser(): void
    {
        $this->client->request('GET', '/admin?routeName=secure_danger_zone', [], [], ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => '1234']);

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertStringNotContainsString(SecureRouteController::SECRET, (string) $this->client->getResponse()->getContent());
    }

    /**
     * The fix must not over-restrict: a user with the required role can still reach the
     * route through a custom action.
     */
    public function testProtectedRouteCanBeReachedThroughRouteNameByAdmin(): void
    {
        $this->client->request('GET', '/admin?routeName=secure_danger_zone', [], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        static::assertResponseIsSuccessful();
        static::assertStringContainsString(SecureRouteController::SECRET, (string) $this->client->getResponse()->getContent());
    }
}
