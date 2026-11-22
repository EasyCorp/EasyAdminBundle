<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch\DeniedSaveActionsCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch\DisabledEditFormBuilderCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch\DisabledEditFormCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch\DisabledSaveActionsCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch\NonBooleanFieldCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch\RemovedSaveActionsCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * The boolean switches of the index page save the entity through the 'edit' action,
 * so they can't change what the edit form doesn't let users change.
 */
class BooleanSwitchTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return DisabledEditFormCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    public function testSwitchOfEditFormDisabledWithFormOptions(): void
    {
        $categoryId = CategoryFactory::createOne(['active' => false])->getId();

        $this->logIn('user');
        $switch = $this->getSwitch(DisabledEditFormCategoryCrudController::class);
        static::assertNotNull($switch->attr('disabled'));
        static::assertSame('', $switch->attr('data-toggle-url'));

        $this->client->request('GET', $this->getCrudUrl('activeToggleCsrfToken', null, [], null, DisabledEditFormCategoryCrudController::class));
        $this->toggle($this->getCrudUrl('edit', $categoryId, ['fieldName' => 'active', 'csrfToken' => $this->client->getResponse()->getContent()], null, DisabledEditFormCategoryCrudController::class));
        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        static::assertFalse($this->isActive($categoryId));

        $this->toggle($this->getToggleUrl('admin', DisabledEditFormCategoryCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertTrue($this->isActive($categoryId));
    }

    #[TestWith(['user', Response::HTTP_BAD_REQUEST, false])]
    #[TestWith(['admin', Response::HTTP_OK, true])]
    public function testSwitchOfEditFormDisabledInFormBuilder(string $username, int $expectedStatusCode, bool $expectedValue): void
    {
        $categoryId = CategoryFactory::createOne(['active' => false])->getId();

        $this->toggle($this->getToggleUrl($username, DisabledEditFormBuilderCategoryCrudController::class));

        static::assertResponseStatusCodeSame($expectedStatusCode);
        static::assertSame($expectedValue, $this->isActive($categoryId));
    }

    public function testSwitchWhenSaveActionsAreDeniedToTheUser(): void
    {
        $categoryId = CategoryFactory::createOne(['active' => false])->getId();
        $adminToggleUrl = $this->getToggleUrl('admin', DeniedSaveActionsCategoryCrudController::class);
        static::assertNotSame('', $adminToggleUrl);

        $this->logIn('user');
        $switch = $this->getSwitch(DeniedSaveActionsCategoryCrudController::class);
        static::assertNotNull($switch->attr('disabled'));
        static::assertSame('', $switch->attr('data-toggle-url'));

        $this->toggle($adminToggleUrl);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertFalse($this->isActive($categoryId));

        $this->toggle($this->getToggleUrl('admin', DeniedSaveActionsCategoryCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertTrue($this->isActive($categoryId));
    }

    public function testSwitchWhenSaveActionsAreDisabled(): void
    {
        $categoryId = CategoryFactory::createOne(['active' => false])->getId();

        $this->logIn('admin');
        $switch = $this->getSwitch(DisabledSaveActionsCategoryCrudController::class);
        static::assertNotNull($switch->attr('disabled'));
        static::assertSame('', $switch->attr('data-toggle-url'));

        $this->toggle($this->getCrudUrl('edit', $categoryId, ['fieldName' => 'active'], null, DisabledSaveActionsCategoryCrudController::class));
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        static::assertFalse($this->isActive($categoryId));
    }

    public function testSwitchWhenSaveActionsAreRemoved(): void
    {
        $categoryId = CategoryFactory::createOne(['active' => false])->getId();

        $this->toggle($this->getToggleUrl('user', RemovedSaveActionsCategoryCrudController::class));

        static::assertResponseIsSuccessful();
        static::assertTrue($this->isActive($categoryId));
    }

    public function testSwitchRequestCantChangeOtherFields(): void
    {
        $categoryId = CategoryFactory::createOne(['name' => 'Category 1', 'active' => false])->getId();

        $this->logIn('admin');
        $this->client->request('GET', $this->getCrudUrl('nameToggleCsrfToken', null, [], null, NonBooleanFieldCategoryCrudController::class));
        $csrfToken = $this->client->getResponse()->getContent();

        $this->toggle($this->getCrudUrl('edit', $categoryId, ['fieldName' => 'name', 'csrfToken' => $csrfToken], null, NonBooleanFieldCategoryCrudController::class));

        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->entityManager->clear();
        static::assertSame('Category 1', $this->entityManager->find(Category::class, $categoryId)->getName());
    }

    private function logIn(string $username): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }

    private function getSwitch(string $controllerFqcn): Crawler
    {
        return $this->client->request('GET', $this->generateIndexUrl(controllerFqcn: $controllerFqcn))->filter('td[data-column="active"] input[type="checkbox"]');
    }

    private function getToggleUrl(string $username, string $controllerFqcn): string
    {
        $this->logIn($username);

        return (string) $this->getSwitch($controllerFqcn)->attr('data-toggle-url');
    }

    private function toggle(string $toggleUrl): void
    {
        $this->client->request('PATCH', $toggleUrl.'&newValue=true', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
    }

    private function isActive(int $categoryId): bool
    {
        $this->entityManager->clear();

        return $this->entityManager->find(Category::class, $categoryId)->isActive();
    }
}
