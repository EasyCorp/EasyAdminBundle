<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FiltersFormType;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\FieldPermission\FieldPermissionInsideTabUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The fields defined inside a form tab, column or fieldset that the user can't see are
 * hidden too, so the search, the sort and the filters of the index page must ignore them.
 */
class FieldPermissionInsideLayoutQueryTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return FieldPermissionInsideTabUserCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    /**
     * @param list<string> $expectedNames
     */
    #[DataProvider('provideSearchQueries')]
    public function testIndexSearch(string $username, string $query, array $expectedNames): void
    {
        $this->createUsers();

        $crawler = $this->requestAs($username, $this->generateIndexUrl($query));

        static::assertSame($expectedNames, $this->getNamesOfRows($crawler));
    }

    /**
     * @param list<string> $expectedNames
     */
    #[DataProvider('provideSearchQueries')]
    public function testAutocompleteSearch(string $username, string $query, array $expectedNames): void
    {
        $this->createUsers();

        $this->requestAs($username, $this->getCrudUrl('autocomplete'), [
            'query' => $query,
            'autocompleteContext' => ['originatingPage' => 'index', 'propertyName' => 'name'],
        ]);

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        static::assertSame($expectedNames, array_column($response['results'], 'entityAsString'));
    }

    public static function provideSearchQueries(): \Generator
    {
        yield 'user, visible field' => ['user', '"User 1"', ['User 1']];
        yield 'user, field inside a restricted tab' => ['user', 'email-b', []];
        yield 'user, field inside a restricted fieldset' => ['user', '"City B"', []];
        yield 'admin, field inside a restricted tab' => ['admin', 'email-b', ['User 1']];
        yield 'admin, field inside a restricted fieldset' => ['admin', '"City B"', ['User 1']];
    }

    /**
     * @param array<string, string> $sort
     * @param list<string>          $expectedNames
     */
    #[DataProvider('provideSorts')]
    public function testIndexSort(string $username, array $sort, array $expectedNames): void
    {
        $this->createUsers();

        $crawler = $this->requestAs($username, $this->generateIndexUrl(), ['sort' => $sort]);

        static::assertSame($expectedNames, $this->getNamesOfRows($crawler));
    }

    public static function provideSorts(): \Generator
    {
        yield 'user, visible field' => ['user', ['name' => 'DESC'], ['User 2', 'User 1']];
        yield 'user, field inside a restricted tab' => ['user', ['email' => 'ASC'], ['User 1', 'User 2']];
        yield 'user, association inside a restricted tab' => ['user', ['favoriteCategory' => 'DESC'], ['User 1', 'User 2']];
        yield 'admin, field inside a restricted tab' => ['admin', ['email' => 'ASC'], ['User 2', 'User 1']];
        yield 'admin, association inside a restricted tab' => ['admin', ['favoriteCategory' => 'DESC'], ['User 2', 'User 1']];
    }

    /**
     * @param list<string> $expectedNames
     */
    #[DataProvider('provideFilters')]
    public function testIndexFilters(string $username, string $property, string $value, array $expectedNames): void
    {
        $this->createUsers();

        $filterName = str_replace('.', FiltersFormType::EMBEDDED_PROPERTY_SEPARATOR, $property);
        $crawler = $this->requestAs($username, $this->generateIndexUrl(), [
            'filters' => [$filterName => ['comparison' => 'like', 'value' => $value]],
        ]);

        static::assertSame($expectedNames, $this->getNamesOfRows($crawler));
    }

    public static function provideFilters(): \Generator
    {
        yield 'user, visible field' => ['user', 'name', 'User 2', ['User 2']];
        yield 'user, field inside a restricted tab' => ['user', 'email', 'email-b', ['User 1', 'User 2']];
        yield 'user, field inside a restricted fieldset' => ['user', 'address.city', 'City B', ['User 1', 'User 2']];
        yield 'admin, field inside a restricted tab' => ['admin', 'email', 'email-b', ['User 1']];
        yield 'admin, field inside a restricted fieldset' => ['admin', 'address.city', 'City B', ['User 1']];
    }

    private function createUsers(): void
    {
        UserFactory::createOne([
            'name' => 'User 1',
            'email' => 'email-b@example.com',
            'address' => (new Address())->setCity('City B'),
            'favoriteCategory' => CategoryFactory::createOne(['name' => 'Category B']),
        ]);
        UserFactory::createOne([
            'name' => 'User 2',
            'email' => 'email-a@example.com',
            'address' => (new Address())->setCity('City A'),
            'favoriteCategory' => CategoryFactory::createOne(['name' => 'Category A']),
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function requestAs(string $username, string $url, array $parameters = []): Crawler
    {
        $crawler = $this->client->request('GET', $url, $parameters, [], ['PHP_AUTH_USER' => $username, 'PHP_AUTH_PW' => '1234']);
        static::assertResponseIsSuccessful();

        return $crawler;
    }

    /**
     * @return list<string>
     */
    private function getNamesOfRows(Crawler $crawler): array
    {
        return $crawler->filter('tbody tr[data-id] td[data-column="name"]')->each(static fn (Crawler $cell): string => trim($cell->text()));
    }
}
