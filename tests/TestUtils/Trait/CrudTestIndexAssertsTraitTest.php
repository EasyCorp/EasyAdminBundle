<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestUtils\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestIndexAsserts;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Config\Action as TestAppAction;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CrudTestIndexAssertsTraitTest extends WebTestCase
{
    use CrudTestIndexAsserts;
    use CrudTestUrlGeneration;

    // the default number of items per page in EasyAdmin
    private const ITEMS_PER_PAGE = 20;

    protected KernelBrowser $client;
    protected AdminUrlGenerator $adminUrlGenerator;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
        // set default authentication for all requests (needed for testing actions with permissions)
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $container = static::getContainer();
        $this->adminUrlGenerator = $container->get(AdminUrlGenerator::class);
    }

    /**
     * @return string returns the tested Controller Fqcn
     */
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    /**
     * @return string returns the tested Controller Fqcn
     */
    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testAssertFullEntityIndexCount(): void
    {
        // more rows than fit in one page, so the full count differs from the rows displayed
        $categories = CategoryFactory::createMany(self::ITEMS_PER_PAGE + 1);
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexFullEntityCount(\count($categories));
    }

    public function testAssertIncorrectFullEntityIndexCountRaisesError(): void
    {
        $categories = CategoryFactory::createMany(3);
        $this->client->request('GET', $this->generateIndexUrl());

        $this->expectException(AssertionFailedError::class);

        self::assertIndexFullEntityCount(\count($categories) + 1);
    }

    public function testAssertZeroFullEntityIndexCountRaisesError(): void
    {
        CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        $this->expectException(AssertionFailedError::class);
        self::assertIndexFullEntityCount(0);
    }

    public function testAssertNegativeFullEntityIndexCountRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->expectException(\InvalidArgumentException::class);
        self::assertIndexFullEntityCount(-1);
    }

    public function testAssertIndexPageEntityCountIsCorrect(): void
    {
        CategoryFactory::createMany(self::ITEMS_PER_PAGE + 1);
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertIndexPageEntityCount(self::ITEMS_PER_PAGE);
    }

    #[DataProvider('pageEntityIncorrectCount')]
    public function testAssertIndexPageEntityIncorrectCountRaisesError(int $incorrectCount, string $expectedException): void
    {
        CategoryFactory::createMany(self::ITEMS_PER_PAGE + 1);
        $this->client->request('GET', $this->generateIndexUrl());

        $this->expectException($expectedException);
        $this->assertIndexPageEntityCount($incorrectCount);
    }

    public static function pageEntityIncorrectCount(): \Generator
    {
        // the tests create 21 items, so the first page shows 20 of them
        yield [0, AssertionFailedError::class];
        yield [-10, \InvalidArgumentException::class];
        yield [21, AssertionFailedError::class];
        yield [30, AssertionFailedError::class];
    }

    public function testAssertIndexPagesCountIsCorrect(): void
    {
        $categories = CategoryFactory::createMany(self::ITEMS_PER_PAGE + 1);
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexPagesCount((int) ceil(\count($categories) / self::ITEMS_PER_PAGE));
    }

    #[DataProvider('pageIncorrectCount')]
    public function testAssertIndexPagesIncorrectCountRaisesError(int $incorrectCount, string $expectedException): void
    {
        CategoryFactory::createMany(self::ITEMS_PER_PAGE + 1);
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException($expectedException);
        self::assertIndexPagesCount($incorrectCount);
    }

    public static function pageIncorrectCount(): \Generator
    {
        // the tests create 21 items, so the index has 2 pages
        yield [0, \InvalidArgumentException::class];
        yield [-10, \InvalidArgumentException::class];
        yield [3, AssertionFailedError::class];
        yield [1, AssertionFailedError::class];
        yield [30, AssertionFailedError::class];
    }

    public function testAssertIndexEntityActionExistsForEntity(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertIndexEntityActionExists(Action::EDIT, $category->getId());
        $this->assertIndexEntityActionExists(Action::DELETE, $category->getId());
    }

    public function testAssertIndexEntityIncorrectActionExistsForEntityRaisesError(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());
        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionExists(Action::INDEX, $category->getId());

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionExists('IncorrectAction', $category->getId());
    }

    public function testAssertIndexIncorrectEntityActionExistsForEntityRaisesError(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionExists(Action::EDIT, $category->getId() + 1);
    }

    public function testAssertNotIndexEntityActionExistsForEntity(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertIndexEntityActionNotExists(Action::INDEX, $category->getId());
        $this->assertIndexEntityActionNotExists('IncorrectAction', $category->getId());
    }

    public function testAssertNotIndexEntityIncorrectActionExistsForEntityRaisesError(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());
        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionNotExists(Action::EDIT, $category->getId());

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionNotExists(Action::DELETE, $category->getId());
    }

    public function testAssertNotIndexIncorrectEntityActionExistsForEntityRaisesError(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionNotExists(Action::INDEX, $category->getId() + 1);
    }

    public function testAssertIndexEntityActionTextSame(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexEntityActionTextSame(Action::EDIT, 'Edit', $category->getId());
        self::assertIndexEntityActionTextSame(Action::DELETE, 'Delete', $category->getId());
    }

    public function testAssertIndexEntityActionNotTextSame(): void
    {
        $category = CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexEntityActionNotTextSame(Action::EDIT, 'edit', $category->getId());
        self::assertIndexEntityActionNotTextSame(Action::EDIT, 'something-else', $category->getId());
        self::assertIndexEntityActionNotTextSame(Action::DELETE, 'delete', $category->getId());
        self::assertIndexEntityActionNotTextSame(Action::DELETE, 'anything', $category->getId());
    }

    public function testAssertGlobalActionExists(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertGlobalActionExists(Action::NEW);
        self::assertGlobalActionExists(TestAppAction::CUSTOM_ACTION);
    }

    public function testAssertGlobalActionExistsIncorrectNameRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionExists(Action::EDIT);

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionExists(TestAppAction::CUSTOM_ACTION);
    }

    public function testAssertGlobalActionNotExists(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertGlobalActionNotExists(Action::EDIT);
        self::assertGlobalActionNotExists('incorrectCustomAction');
    }

    public function testAssertGlobalActionNotExistsCorrectActionRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionNotExists(Action::NEW);

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionNotExists(TestAppAction::CUSTOM_ACTION);
    }

    public function testAssertGlobalActionDisplays(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertGlobalActionDisplays(Action::NEW, 'Add Category');
        self::assertGlobalActionDisplays(TestAppAction::CUSTOM_ACTION, 'Custom Action');
    }

    public function testAssertGlobalActionDisplaysIncorrectValuesRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionDisplays(Action::NEW, 'add Category');

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionDisplays(Action::NEW, 'Incorrect value');

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionDisplays(TestAppAction::CUSTOM_ACTION, 'custom Action');
    }

    public function testAssertGlobalActionNotDisplays(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertGlobalActionNotDisplays(Action::NEW, 'New Category');
        self::assertGlobalActionNotDisplays(TestAppAction::CUSTOM_ACTION, 'incorrectCustomAction');
    }

    public function testAssertGlobalActionNotDisplaysCorrectValueRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionNotDisplays(Action::NEW, 'Add Category');

        self::expectException(AssertionFailedError::class);
        self::assertGlobalActionNotDisplays(TestAppAction::CUSTOM_ACTION, 'Custom Action');
    }

    #[DataProvider('existingColumns')]
    public function testAssertIndexColumnExists(string $columnName): void
    {
        $this->requestIndexWithOneCategory();

        self::assertIndexColumnExists($columnName);
    }

    public function testAssertIndexIncorrectColumnExistsRaisesError(): void
    {
        $this->requestIndexWithOneCategory();

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnExists('ID');

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnExists('delete');
    }

    public function testAssertIndexColumnNotExists(): void
    {
        $this->requestIndexWithOneCategory();

        self::assertIndexColumnNotExists('ID');
        self::assertIndexColumnNotExists('IncorrectColumnID');
    }

    #[DataProvider('existingColumns')]
    public function testAssertIndexCorrectColumnNotExistsRaisesError(string $columnName): void
    {
        $this->requestIndexWithOneCategory();

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnNotExists($columnName);
    }

    public static function existingColumns(): \Generator
    {
        yield ['id'];
        yield ['name'];
        yield ['slug'];
        yield ['active'];
    }

    #[DataProvider('existingColumnsDisplayValues')]
    public function testAssertColumnHeaderContains(string $columnName, string $displayValue): void
    {
        $this->requestIndexWithOneCategory();

        self::assertIndexColumnHeaderContains($columnName, $displayValue);
    }

    public function testAssertColumnHeaderContainsIncorrectValueRaisesError(): void
    {
        $this->requestIndexWithOneCategory();

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnHeaderContains('id', 'id');
        self::assertIndexColumnHeaderContains('id', 'another value');
    }

    public static function existingColumnsDisplayValues(): \Generator
    {
        yield ['id', 'ID'];
        yield ['name', 'Name'];
        yield ['slug', 'Slug'];
        yield ['active', 'Active'];
    }

    public function testAssertColumnHeaderNotContains(): void
    {
        $this->requestIndexWithOneCategory();

        self::assertIndexColumnHeaderNotContains('id', 'id');
        self::assertIndexColumnHeaderNotContains('id', 'incorrect Value');
    }

    #[DataProvider('existingColumnsDisplayValues')]
    public function testAssertColumnHeaderNotContainsCorrectValueRaisesError(string $columnName, string $displayValue): void
    {
        $this->requestIndexWithOneCategory();

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnHeaderNotContains($columnName, $displayValue);
    }

    private function requestIndexWithOneCategory(): void
    {
        // the table header is only rendered when there are results
        CategoryFactory::createOne();
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertIndexPageEntityCount(1);
    }
}
