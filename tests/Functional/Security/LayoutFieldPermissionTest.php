<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission\RestrictedColumnUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission\RestrictedFirstTabUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission\RestrictedTabCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission\RestrictedTabUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpFoundation\Response;

/**
 * The permission of a form tab, column or fieldset also applies to the fields defined inside it.
 */
class LayoutFieldPermissionTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return RestrictedTabUserCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    #[TestWith(['user', 0])]
    #[TestWith(['admin', 1])]
    public function testIndexPage(string $username, int $expectedCount): void
    {
        $this->createUser();
        $this->logIn($username);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('th[data-column="name"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="address.city"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="email"]'));
        static::assertCount($expectedCount, $crawler->filter('td:contains("email1@example.com")'));
    }

    #[TestWith(['user', 0])]
    #[TestWith(['admin', 1])]
    public function testDetailPage(string $username, int $expectedCount): void
    {
        $userId = $this->createUser();
        $this->logIn($username);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($userId));

        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('#tab-profile', 'User 1');
        static::assertCount(1 + $expectedCount, $crawler->filter('.nav-tabs .nav-link'));
        static::assertCount($expectedCount, $crawler->filter('.field-value:contains("City 1")'));
        static::assertCount($expectedCount, $crawler->filter('.field-value:contains("email1@example.com")'));
    }

    #[TestWith(['user', 0])]
    #[TestWith(['admin', 1])]
    public function testFormPages(string $username, int $expectedCount): void
    {
        $userId = $this->createUser();
        $this->logIn($username);

        foreach ([$this->generateEditFormUrl($userId), $this->generateNewFormUrl()] as $formUrl) {
            $crawler = $this->client->request('GET', $formUrl);

            static::assertResponseIsSuccessful();
            static::assertCount(1, $crawler->filter('#tab-profile input[name="User[name]"]'));
            static::assertCount(1 + $expectedCount, $crawler->filter('.nav-tabs .nav-link'));
            static::assertCount($expectedCount, $crawler->filter('input[name="User[address_city]"]'));
            static::assertCount($expectedCount, $crawler->filter('input[name="User[email]"]'));
        }
    }

    public function testEditFormDoesNotSaveFieldsOfRestrictedTab(): void
    {
        $userId = $this->createUser();
        $this->logIn('user');

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($userId));
        $formValues = $crawler->filter('form[name="User"]')->form()->getPhpValues();
        $formValues['User']['email'] = 'email2@example.com';
        $formValues['User']['address_city'] = 'City 2';
        $this->client->request('POST', $this->generateEditFormUrl($userId), $formValues);

        $this->entityManager->clear();
        $user = $this->entityManager->find(User::class, $userId);
        static::assertSame('email1@example.com', $user->getEmail());
        static::assertSame('City 1', $user->getAddress()->getCity());
    }

    #[TestWith(['user', 0])]
    #[TestWith(['admin', 1])]
    public function testRestrictedFirstTab(string $username, int $expectedCount): void
    {
        $userId = $this->createUser();
        $this->logIn($username);

        $urls = [
            $this->generateDetailUrl($userId, null, RestrictedFirstTabUserCrudController::class),
            $this->generateEditFormUrl($userId, null, RestrictedFirstTabUserCrudController::class),
            $this->generateNewFormUrl(null, RestrictedFirstTabUserCrudController::class),
        ];
        foreach ($urls as $url) {
            $crawler = $this->client->request('GET', $url);

            static::assertResponseIsSuccessful();
            static::assertCount(1 + $expectedCount, $crawler->filter('.nav-tabs .nav-link'));
            static::assertCount($expectedCount, $crawler->filter('.field-value:contains("email1@example.com"), input[name="User[email]"]'));
        }
    }

    #[TestWith(['user', 0])]
    #[TestWith(['admin', 1])]
    public function testRestrictedColumn(string $username, int $expectedCount): void
    {
        $userId = $this->createUser();
        $this->logIn($username);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(controllerFqcn: RestrictedColumnUserCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('th[data-column="address.street"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="email"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="address.city"]'));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($userId, null, RestrictedColumnUserCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.field-value:contains("User 1")'));
        static::assertCount(1, $crawler->filter('.field-value:contains("123")'));
        static::assertCount($expectedCount, $crawler->filter('.field-value:contains("email1@example.com")'));
        static::assertCount($expectedCount, $crawler->filter('.field-value:contains("City 1")'));

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($userId, null, RestrictedColumnUserCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('input[name="User[name]"]'));
        static::assertCount(1, $crawler->filter('input[name="User[address_street]"]'));
        static::assertCount($expectedCount, $crawler->filter('input[name="User[email]"]'));
        static::assertCount($expectedCount, $crawler->filter('input[name="User[address_city]"]'));
    }

    #[TestWith(['user', Response::HTTP_BAD_REQUEST, false])]
    #[TestWith(['admin', Response::HTTP_OK, true])]
    public function testBooleanSwitchInsideRestrictedTab(string $username, int $expectedStatusCode, bool $expectedValue): void
    {
        $categoryId = CategoryFactory::createOne(['name' => 'Category 1', 'active' => false])->getId();
        $this->logIn($username);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(controllerFqcn: RestrictedTabCategoryCrudController::class));
        static::assertCount((int) $expectedValue, $crawler->filter('td[data-column="active"]'));

        $this->client->request('GET', $this->getCrudUrl('activeToggleCsrfToken', null, [], null, RestrictedTabCategoryCrudController::class));
        $toggleUrl = $this->getCrudUrl('edit', $categoryId, ['fieldName' => 'active', 'csrfToken' => $this->client->getResponse()->getContent(), 'newValue' => 'true'], null, RestrictedTabCategoryCrudController::class);
        $this->client->request('PATCH', $toggleUrl, [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        static::assertResponseStatusCodeSame($expectedStatusCode);
        $this->entityManager->clear();
        static::assertSame($expectedValue, $this->entityManager->find(Category::class, $categoryId)->isActive());
    }

    private function createUser(): int
    {
        return UserFactory::createOne([
            'name' => 'User 1',
            'email' => 'email1@example.com',
            'address' => (new Address())->setCity('City 1')->setStreet(123),
        ])->getId();
    }

    private function logIn(string $username): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }
}
