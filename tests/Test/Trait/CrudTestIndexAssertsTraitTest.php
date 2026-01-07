<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Test\Trait;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestIndexAsserts;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Config\Action as TestAppAction;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CrudTestIndexAssertsTraitTest extends WebTestCase
{
    use CrudTestIndexAsserts;
    use CrudTestUrlGeneration;

    protected KernelBrowser $client;
    protected AdminUrlGenerator $adminUrlGenerator;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
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
        return SecureDashboardController::class;
    }

    public function testAssertFullEntityIndexCount(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $allCategories = $this->entityManager->getRepository(Category::class)->findAll();
        self::assertIndexFullEntityCount(\count($allCategories));
    }

    public function testAssertIncorrectFullEntityIndexCountRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->expectException(AssertionFailedError::class);

        $allCategories = $this->entityManager->getRepository(Category::class)->findAll();
        self::assertIndexFullEntityCount(\count($allCategories) + 1);
    }

    public function testAssertZeroFullEntityIndexCountRaisesError(): void
    {
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
        $this->client->request('GET', $this->generateIndexUrl());
        $this->assertIndexPageEntityCount(20); // 20 items per page is the default for EasyAdmin
    }

    /**
     * @dataProvider pageEntityIncorrectCount
     */
    public function testAssertIndexPageEntityIncorrectCountRaisesError(int $incorrectCount, string $expectedException): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->expectException($expectedException);
        $this->assertIndexPageEntityCount($incorrectCount);
    }

    public static function pageEntityIncorrectCount(): \Generator
    {
        // 20 items per page is the default for EasyAdmin
        yield [0, AssertionFailedError::class];
        yield [-10, \InvalidArgumentException::class];
        yield [21, AssertionFailedError::class];
        yield [30, AssertionFailedError::class];
    }

    public function testAssertIndexPagesCountIsCorrect(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $allCategories = $this->entityManager->getRepository(Category::class)->findAll();
        self::assertIndexPagesCount((int) ceil(\count($allCategories) / 20)); // 20 items per page is the default for EasyAdmin
    }

    /**
     * @dataProvider pageIncorrectCount
     */
    public function testAssertIndexPagesIncorrectCountRaisesError(int $incorrectCount, string $expectedException): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException($expectedException);
        self::assertIndexPagesCount($incorrectCount);
    }

    public static function pageIncorrectCount(): \Generator
    {
        // 20 items per page is the default for EasyAdmin
        yield [0, \InvalidArgumentException::class];
        yield [-10, \InvalidArgumentException::class];
        yield [3, AssertionFailedError::class];
        yield [1, AssertionFailedError::class];
        yield [30, AssertionFailedError::class];
    }

    public function testAssertIndexEntityActionExistsForEntity(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        /** @var Category $category */
        $category = $this->entityManager->getRepository(Category::class)->findBy([], ['id' => 'ASC'])[0];

        $this->assertIndexEntityActionExists(Action::EDIT, $category->getId());
        $this->assertIndexEntityActionExists(Action::DELETE, $category->getId());
    }

    public function testAssertIndexEntityIncorrectActionExistsForEntityRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());
        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionExists(Action::INDEX, 1);

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionExists('IncorrectAction', 1);
    }

    public function testAssertIndexIncorrectEntityActionExistsForEntityRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionExists(Action::EDIT, 0);
    }

    public function testAssertNotIndexEntityActionExistsForEntity(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        /** @var Category $category */
        $category = $this->entityManager->getRepository(Category::class)->findBy([], ['id' => 'ASC'])[0];

        $this->assertIndexEntityActionNotExists(Action::INDEX, $category->getId());
        $this->assertIndexEntityActionNotExists('IncorrectAction', $category->getId());
    }

    public function testAssertNotIndexEntityIncorrectActionExistsForEntityRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());
        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionNotExists(Action::EDIT, 1);

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionNotExists(Action::DELETE, 1);
    }

    public function testAssertNotIndexIncorrectEntityActionExistsForEntityRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        $this->assertIndexEntityActionNotExists(Action::INDEX, 0);
    }

    public function testAssertIndexEntityActionTextSame(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        /** @var Category $category */
        $category = $this->entityManager->getRepository(Category::class)->findBy([], ['id' => 'ASC'])[0];

        self::assertIndexEntityActionTextSame(Action::EDIT, 'Edit', $category->getId());
        self::assertIndexEntityActionTextSame(Action::DELETE, 'Delete', $category->getId());
    }

    public function testAssertIndexEntityActionNotTextSame(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        /** @var Category $category */
        $category = $this->entityManager->getRepository(Category::class)->findBy([], ['id' => 'ASC'])[0];

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

    /**
     * @dataProvider existingColumns
     */
    public function testAssertIndexColumnExists(string $columnName): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexColumnExists($columnName);
    }

    public function testAssertIndexIncorrectColumnExistsRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnExists('ID');

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnExists('delete');
    }

    public function testAssertIndexColumnNotExists(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexColumnNotExists('ID');
        self::assertIndexColumnNotExists('IncorrectColumnID');
    }

    /**
     * @dataProvider existingColumns
     */
    public function testAssertIndexCorrectColumnNotExistsRaisesError(string $columnName): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

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

    /**
     * @dataProvider existingColumnsDisplayValues
     */
    public function testAssertColumnHeaderContains(string $columnName, string $displayValue): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexColumnHeaderContains($columnName, $displayValue);
    }

    public function testAssertColumnHeaderContainsIncorrectValueRaisesError(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

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
        $this->client->request('GET', $this->generateIndexUrl());

        self::assertIndexColumnHeaderNotContains('id', 'id');
        self::assertIndexColumnHeaderNotContains('id', 'incorrect Value');
    }

    /**
     * @dataProvider existingColumnsDisplayValues
     */
    public function testAssertColumnHeaderNotContainsCorrectValueRaisesError(string $columnName, string $displayValue): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        self::assertIndexColumnHeaderNotContains($columnName, $displayValue);
    }
}
