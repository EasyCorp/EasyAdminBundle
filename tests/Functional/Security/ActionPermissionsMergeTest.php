<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ActionPermissions\ActionPermissionsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ActionPermissions\ExtendedPermissionsCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ActionPermissions\LiftedPermissionCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ActionPermissions\SetPermissionsCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks that setPermissions() adds permissions to the ones defined before on the
 * same Actions object (by the dashboard, the CRUD controller or an actions extension)
 * instead of replacing them.
 */
class ActionPermissionsMergeTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return SetPermissionsCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return ActionPermissionsDashboardController::class;
    }

    /**
     * @param class-string $crudControllerFqcn
     */
    #[DataProvider('provideDeleteAccess')]
    public function testDeletePermissionOfDashboardIsKept(string $crudControllerFqcn, string $username, bool $canDelete): void
    {
        $categoryId = CategoryFactory::createOne()->getId();

        $crawler = $this->requestAs($username, 'GET', $this->generateDetailUrl($categoryId, null, $crudControllerFqcn));
        static::assertResponseIsSuccessful();
        $deleteButtonCount = $crawler->filter('.page-actions [data-action-name="'.Action::DELETE.'"]')->count();

        $this->requestAs($username, 'POST', $this->getCrudUrl(Action::DELETE, $categoryId, [], null, $crudControllerFqcn), [
            'token' => $crawler->filter('#action-confirmation-form input[name="token"]')->attr('value'),
        ]);

        // a refused CSRF token also redirects, so only the database tells whether the delete ran
        $this->entityManager->clear();
        static::assertSame(!$canDelete, null !== $this->entityManager->find(Category::class, $categoryId));
        static::assertResponseStatusCodeSame($canDelete ? Response::HTTP_FOUND : Response::HTTP_FORBIDDEN);
        static::assertSame($canDelete ? 1 : 0, $deleteButtonCount);
    }

    public static function provideDeleteAccess(): \Generator
    {
        yield 'setPermissions() in the CRUD controller, user' => [SetPermissionsCategoryCrudController::class, 'user', false];
        yield 'setPermissions() in the CRUD controller, admin' => [SetPermissionsCategoryCrudController::class, 'admin', true];
        yield 'setPermissions() in an actions extension, user' => [ExtendedPermissionsCategoryCrudController::class, 'user', false];
        yield 'setPermissions() in an actions extension, admin' => [ExtendedPermissionsCategoryCrudController::class, 'admin', true];
        yield 'permission removed with an empty string, user' => [LiftedPermissionCategoryCrudController::class, 'user', true];
    }

    public function testBatchDeleteChecksDeletePermissionOfDashboard(): void
    {
        $categoryId = CategoryFactory::createOne()->getId();

        // the batch delete CSRF token is shared by all CRUD controllers of the same entity,
        // so take it from the one where the user is allowed to delete
        $crawler = $this->requestAs('user', 'GET', $this->generateIndexUrl(null, null, LiftedPermissionCategoryCrudController::class));
        $batchDeleteButton = $crawler->filter('.batch-actions [data-action-name="'.Action::BATCH_DELETE.'"]');
        static::assertCount(1, $batchDeleteButton);

        $batchActionUrl = $this->getCrudUrl(Action::BATCH_DELETE, null, [], null, SetPermissionsCategoryCrudController::class);
        $this->requestAs('user', 'POST', $batchActionUrl, [
            'batchActionName' => Action::BATCH_DELETE,
            'entityFqcn' => Category::class,
            'batchActionUrl' => $batchActionUrl,
            'batchActionCsrfToken' => $batchDeleteButton->attr('data-action-csrf-token'),
            'batchActionEntityIds' => [$categoryId],
        ]);

        $this->entityManager->clear();
        static::assertNotNull($this->entityManager->find(Category::class, $categoryId));
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @param class-string $crudControllerFqcn
     */
    #[DataProvider('provideNewAccess')]
    public function testPermissionsGivenToSetPermissionsAreApplied(string $crudControllerFqcn, string $username, int $expectedStatusCode): void
    {
        $this->requestAs($username, 'GET', $this->generateNewFormUrl(null, null, $crudControllerFqcn));

        static::assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideNewAccess(): \Generator
    {
        yield 'setPermissions() in the CRUD controller, user' => [SetPermissionsCategoryCrudController::class, 'user', Response::HTTP_FORBIDDEN];
        yield 'setPermissions() in the CRUD controller, admin' => [SetPermissionsCategoryCrudController::class, 'admin', Response::HTTP_OK];
        yield 'setPermissions() in an actions extension, user' => [ExtendedPermissionsCategoryCrudController::class, 'user', Response::HTTP_FORBIDDEN];
        yield 'setPermissions() in an actions extension, admin' => [ExtendedPermissionsCategoryCrudController::class, 'admin', Response::HTTP_OK];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function requestAs(string $username, string $method, string $url, array $parameters = []): Crawler
    {
        return $this->client->request($method, $url, $parameters, [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }
}
