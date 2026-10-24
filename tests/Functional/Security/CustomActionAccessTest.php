<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CustomActionAccessCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Anchors the "Security" rules of skills/easyadmin/SKILL.md: the permission and
 * the disabled state of a custom CRUD action are enforced when its URL is requested,
 * not only when its button is rendered.
 *
 * If this behavior changes, update that skill rule too.
 */
class CustomActionAccessTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return CustomActionAccessCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    #[DataProvider('provideCustomActionAccess')]
    public function testCustomActionUrlAccess(string $crudActionName, bool $withEntity, string $username, bool $isGranted): void
    {
        $entityId = $withEntity ? CategoryFactory::createOne()->getId() : null;

        if (!$isGranted) {
            $this->expectException(ForbiddenActionException::class);
            $this->client->catchExceptions(false);
        }

        $this->requestAs($username, 'GET', $this->getCrudUrl($crudActionName, $entityId));

        static::assertResponseIsSuccessful();
    }

    public static function provideCustomActionAccess(): \Generator
    {
        yield 'entity action whose name differs from the method, denied' => ['approveCategory', true, 'user', false];
        yield 'entity action whose name differs from the method, granted' => ['approveCategory', true, 'admin', true];
        yield 'disabled custom action, denied even to super admins' => ['archiveCategory', true, 'super_admin', false];
        yield 'method linked from two actions, none granted' => ['publishCategory', true, 'user', false];
        yield 'method linked from two actions, one granted' => ['publishCategory', true, 'admin', true];
        yield 'action inside an action group, denied' => ['exportCategories', false, 'user', false];
        yield 'action inside an action group, granted' => ['exportCategories', false, 'admin', true];
        yield 'route of this controller used in linkToRoute(), denied' => ['sendReport', false, 'user', false];
        yield 'route of this controller used in linkToRoute(), granted' => ['sendReport', false, 'admin', true];
        yield 'route alias used in linkToRoute(), denied' => ['sendReminder', false, 'user', false];
        yield 'route alias used in linkToRoute(), granted' => ['sendReminder', false, 'admin', true];
        yield 'method not linked to any action stays reachable' => ['showStats', false, 'user', true];
    }

    /**
     * Admin routes reached through '?routeName=' must be checked with the configuration
     * of their own CRUD controller, not with the one of the URL that forwards them.
     */
    #[DataProvider('provideAdminRoutesReachedThroughRouteName')]
    public function testAdminRouteReachedThroughRouteNameIsRedirectedAndChecked(string $forwardingUrl, string $routeName): void
    {
        // aliases are resolved, so the redirection always goes to the URL of the admin route itself
        $targetUrl = static::getContainer()->get('router')->generate($routeName);

        $this->requestAs('user', 'GET', $forwardingUrl.'?routeName='.$routeName);
        static::assertResponseStatusCodeSame(307);
        static::assertResponseRedirects($targetUrl);

        $this->expectException(ForbiddenActionException::class);
        $this->client->catchExceptions(false);
        $this->client->followRedirect();
    }

    public static function provideAdminRoutesReachedThroughRouteName(): \Generator
    {
        yield 'custom action linked with linkToRoute()' => ['/admin', 'secured_admin_custom_action_access_send_report'];
        yield 'disabled custom action' => ['/admin', 'secured_admin_custom_action_access_archive_category'];
        yield 'custom action with a permission' => ['/admin', 'secured_admin_category_admin_only'];
        yield 'built-in action of another CRUD controller' => ['/admin/category', 'secured_admin_protected_category_index'];
        yield 'alias of an admin route' => ['/admin', 'send_report_alias'];
    }

    public function testAdminRouteReachedThroughRouteNameKeepsTheHttpMethodAndBody(): void
    {
        $this->client->request('POST', '/admin?routeName=secured_admin_custom_action_access_activate_categories', ['sentinel' => 'f3a9c1'], [], ['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
        static::assertResponseStatusCodeSame(307);

        $this->client->followRedirect();
        static::assertResponseIsSuccessful();
        static::assertSame('POST', $this->client->getRequest()->getMethod());
        static::assertSame('Categories activated (f3a9c1)', $this->client->getResponse()->getContent());
    }

    public function testBatchActionUrlAccessIsDenied(): void
    {
        $this->expectException(ForbiddenActionException::class);
        $this->client->catchExceptions(false);

        $this->requestAs('user', 'POST', $this->getCrudUrl('activateCategories'));
    }

    public function testBatchActionUrlAccessIsGranted(): void
    {
        $this->requestAs('admin', 'POST', $this->getCrudUrl('activateCategories'));

        static::assertResponseIsSuccessful();
    }

    public function testDisabledBuiltInActionUrlAccessIsDenied(): void
    {
        $this->expectException(ForbiddenActionException::class);
        $this->client->catchExceptions(false);

        $this->requestAs('super_admin', 'GET', $this->generateNewFormUrl());
    }

    public function testDeniedCustomActionRendersErrorPage(): void
    {
        $this->requestAs('user', 'GET', $this->getCrudUrl('approveCategory', CategoryFactory::createOne()->getId()));

        static::assertResponseStatusCodeSame(403);
        static::assertSelectorTextContains('.error-message', "can't be performed on this item.");
    }

    public function testDeniedActionsAreNotRendered(): void
    {
        CategoryFactory::createOne();

        $crawler = $this->requestAs('user', 'GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('[data-action-name="approve"]'));
        static::assertCount(0, $crawler->filter('[data-action-name="archive"]'));
        static::assertCount(0, $crawler->filter('[data-action-name="'.Action::NEW.'"]'));
    }

    private function requestAs(string $username, string $method, string $url): Crawler
    {
        return $this->client->request($method, $url, [], [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }
}
