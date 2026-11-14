<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UglyUrls\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\CustomActionAccessCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Kernel;

/**
 * The permission and the disabled state of a custom CRUD action are enforced when
 * its legacy URL (?crudControllerFqcn=...&crudAction=...) is requested in an application
 * that doesn't use pretty URLs. The user is anonymous, so they don't have any role.
 */
class UglyUrlsCustomActionAccessTest extends AbstractCrudTestCase
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
        return DashboardController::class;
    }

    /**
     * @dataProvider provideDeniedCustomActions
     */
    public function testDeniedCustomActionUrlIsNotExecuted(string $method, string $crudActionName): void
    {
        $this->expectException(ForbiddenActionException::class);
        $this->client->catchExceptions(false);

        $this->client->request($method, $this->getLegacyUrl($crudActionName));
    }

    public static function provideDeniedCustomActions(): \Generator
    {
        yield 'custom action with a permission' => ['GET', 'approveCategory'];
        yield 'disabled custom action' => ['GET', 'archiveCategory'];
        yield 'custom batch action with a permission' => ['POST', 'activateCategories'];
    }

    public function testMethodNotLinkedToAnyActionStaysReachable(): void
    {
        $this->client->request('GET', $this->getLegacyUrl('showStats'));

        static::assertResponseIsSuccessful();
        static::assertSame('Category stats', $this->client->getResponse()->getContent());
    }

    private function getLegacyUrl(string $crudActionName): string
    {
        return sprintf('/admin?%s', http_build_query([
            EA::CRUD_CONTROLLER_FQCN => CustomActionAccessCrudController::class,
            EA::CRUD_ACTION => $crudActionName,
        ]));
    }
}
