<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission\RestrictedColumnCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission\RestrictedFirstTabCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission\RestrictedTabCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\HttpFoundation\Response;

/**
 * The permission of a form tab, column or fieldset also applies to the fields defined inside it.
 *
 * The test app database is shared by all tests, so this test creates its own category and
 * removes it afterwards.
 */
class LayoutFieldPermissionTest extends AbstractCrudTestCase
{
    private const SLUG = 'layout-permission-category-slug';

    private ?int $categoryId = null;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return RestrictedTabCategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $category = (new Category())
            ->setName('Layout Permission Category')
            ->setSlug(self::SLUG)
            ->setActive(false);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        $this->categoryId = $category->getId();
    }

    protected function tearDown(): void
    {
        if (null !== $this->categoryId) {
            $this->entityManager->clear();
            if (null !== $category = $this->entityManager->find(Category::class, $this->categoryId)) {
                $this->entityManager->remove($category);
                $this->entityManager->flush();
            }
        }

        parent::tearDown();
    }

    /**
     * @dataProvider provideUsers
     */
    public function testIndexPage(string $username, int $expectedCount): void
    {
        $this->logIn($username);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('th[data-column="name"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="slug"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="active"]'));
    }

    /**
     * @dataProvider provideUsers
     */
    public function testDetailPage(string $username, int $expectedCount): void
    {
        $this->logIn($username);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($this->categoryId));

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.field-value:contains("Layout Permission Category")'));
        static::assertCount(1 + $expectedCount, $crawler->filter('.nav-tabs .nav-link'));
        static::assertCount($expectedCount, $crawler->filter('.field-value:contains("'.self::SLUG.'")'));
    }

    /**
     * @dataProvider provideUsers
     */
    public function testFormPages(string $username, int $expectedCount): void
    {
        $this->logIn($username);

        foreach ([$this->generateEditFormUrl($this->categoryId), $this->generateNewFormUrl()] as $formUrl) {
            $crawler = $this->client->request('GET', $formUrl);

            static::assertResponseIsSuccessful();
            static::assertCount(1, $crawler->filter('input[name="Category[name]"]'));
            static::assertCount(1 + $expectedCount, $crawler->filter('.nav-tabs .nav-link'));
            static::assertCount($expectedCount, $crawler->filter('input[name="Category[slug]"]'));
            static::assertCount($expectedCount, $crawler->filter('input[name="Category[active]"]'));
        }
    }

    public function testEditFormDoesNotSaveFieldsOfRestrictedContainers(): void
    {
        $this->logIn('user');

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->categoryId));
        $formValues = $crawler->filter($this->getEntityFormSelector())->form()->getPhpValues();
        $formValues['Category']['slug'] = 'changed-slug';
        $formValues['Category']['active'] = '1';
        $this->client->request('POST', $this->generateEditFormUrl($this->categoryId), $formValues);

        $this->entityManager->clear();
        $category = $this->entityManager->find(Category::class, $this->categoryId);
        static::assertSame(self::SLUG, $category->getSlug());
        static::assertFalse($category->isActive());
    }

    /**
     * @dataProvider provideUsers
     */
    public function testRestrictedFirstTab(string $username, int $expectedCount): void
    {
        $this->logIn($username);

        $urls = [
            $this->generateDetailUrl($this->categoryId, null, RestrictedFirstTabCategoryCrudController::class),
            $this->generateEditFormUrl($this->categoryId, null, RestrictedFirstTabCategoryCrudController::class),
            $this->generateNewFormUrl(null, RestrictedFirstTabCategoryCrudController::class),
        ];
        foreach ($urls as $url) {
            $crawler = $this->client->request('GET', $url);

            static::assertResponseIsSuccessful();
            static::assertCount(1 + $expectedCount, $crawler->filter('.nav-tabs .nav-link'));
            static::assertCount($expectedCount, $crawler->filter('.field-value:contains("'.self::SLUG.'"), input[name="Category[slug]"]'));
        }
    }

    /**
     * @dataProvider provideUsers
     */
    public function testRestrictedColumn(string $username, int $expectedCount): void
    {
        $this->logIn($username);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, null, RestrictedColumnCategoryCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('th[data-column="activeDisabled"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="slug"]'));
        static::assertCount($expectedCount, $crawler->filter('th[data-column="active"]'));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($this->categoryId, null, RestrictedColumnCategoryCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.field-value:contains("Layout Permission Category")'));
        static::assertCount($expectedCount, $crawler->filter('.field-value:contains("'.self::SLUG.'")'));

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->categoryId, null, RestrictedColumnCategoryCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('input[name="Category[name]"]'));
        static::assertCount(1, $crawler->filter('input[name="Category[activeDisabled]"]'));
        static::assertCount($expectedCount, $crawler->filter('input[name="Category[slug]"]'));
        static::assertCount($expectedCount, $crawler->filter('input[name="Category[active]"]'));
    }

    /**
     * @dataProvider provideBooleanSwitchResults
     */
    public function testBooleanSwitchInsideRestrictedTab(string $username, int $expectedStatusCode, bool $expectedValue): void
    {
        $this->logIn($username);

        $this->client->request('GET', $this->getCrudUrl('toggleCsrfToken'));
        $toggleUrl = $this->getCrudUrl('edit', $this->categoryId, ['fieldName' => 'active', 'csrfToken' => $this->client->getResponse()->getContent(), 'newValue' => 'true']);
        $this->client->request('PATCH', $toggleUrl, [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        static::assertResponseStatusCodeSame($expectedStatusCode);
        $this->entityManager->clear();
        static::assertSame($expectedValue, $this->entityManager->find(Category::class, $this->categoryId)->isActive());

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        static::assertCount((int) $expectedValue, $crawler->filter('th[data-column="active"]'));
    }

    public static function provideUsers(): \Generator
    {
        yield 'user' => ['user', 0];
        yield 'admin' => ['admin', 1];
    }

    public static function provideBooleanSwitchResults(): \Generator
    {
        yield 'user' => ['user', Response::HTTP_BAD_REQUEST, false];
        yield 'admin' => ['admin', Response::HTTP_OK, true];
    }

    private function logIn(string $username): void
    {
        $this->client->setServerParameters(['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
    }
}
