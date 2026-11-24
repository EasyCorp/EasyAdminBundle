<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UglyUrls\Security;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Kernel;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The URLs that change an entity (the delete button and the boolean switch) must
 * point to the entity of their row, whatever the query string of the current page.
 * Same as the test of the secured app, but with the legacy URLs, where the CRUD
 * action and the entity ID are query parameters.
 *
 * @group legacy
 */
class EntityActionUrlTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;
    /** @var list<int> */
    private array $createdCategoryIds = [];

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
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    protected function tearDown(): void
    {
        if ([] !== $this->createdCategoryIds) {
            $this->entityManager->clear();
            foreach ($this->categories->findBy(['id' => $this->createdCategoryIds]) as $category) {
                $this->entityManager->remove($category);
            }
            $this->entityManager->flush();
        }

        parent::tearDown();
    }

    public function testDeleteButtonDeletesTheEntityOfItsRow(): void
    {
        $searchQuery = $this->getUniqueSearchQuery();
        $otherCategoryId = $this->createCategory($searchQuery.' 1');
        $categoryId = $this->createCategory($searchQuery.' 2');

        $crawler = $this->client->request('GET', $this->generateIndexUrl($searchQuery).'&'.$this->getUnrelatedQueryString($otherCategoryId));
        $deleteUrl = $this->getRow($crawler, $categoryId)->filter('.action-delete')->attr('formaction');

        static::assertNull(parse_url($deleteUrl, \PHP_URL_FRAGMENT));
        static::assertSame([EA::CRUD_ACTION => Action::DELETE, EA::CRUD_CONTROLLER_FQCN => CategoryCrudController::class, EA::ENTITY_ID => (string) $categoryId, EA::QUERY => $searchQuery], $this->getQueryParameters($deleteUrl));

        $this->client->request('POST', $deleteUrl, ['token' => $crawler->filter('#action-confirmation-form input[name="token"]')->attr('value')]);

        $this->entityManager->clear();
        static::assertNull($this->categories->find($categoryId));
        static::assertNotNull($this->categories->find($otherCategoryId));
    }

    public function testDeleteButtonKeepsTheStateOfTheListing(): void
    {
        $searchQuery = $this->getUniqueSearchQuery();
        $categoryId = $this->createCategory($searchQuery);

        $crawler = $this->client->request('GET', $this->generateIndexUrl($searchQuery).'&page=1&sort[name]=ASC');
        $deleteUrl = $this->getRow($crawler, $categoryId)->filter('.action-delete')->attr('formaction');
        $parameters = $this->getQueryParameters($deleteUrl);

        static::assertSame('1', $parameters[EA::PAGE]);
        static::assertSame($searchQuery, $parameters[EA::QUERY]);
        static::assertSame(['name' => 'ASC'], $parameters[EA::SORT]);
    }

    public function testSwitchTogglesTheFieldAndTheEntityOfItsRow(): void
    {
        $searchQuery = $this->getUniqueSearchQuery();
        $otherCategoryId = $this->createCategory($searchQuery.' 1');
        $categoryId = $this->createCategory($searchQuery.' 2');

        $crawler = $this->client->request('GET', $this->generateIndexUrl($searchQuery).'&'.$this->getUnrelatedQueryString($otherCategoryId));
        $toggleUrl = $this->getRow($crawler, $categoryId)->filter('td[data-column="active"] input[type="checkbox"]')->attr('data-toggle-url');

        static::assertNull(parse_url($toggleUrl, \PHP_URL_FRAGMENT));
        $parameters = $this->getQueryParameters($toggleUrl);
        static::assertSame([EA::CRUD_ACTION, EA::CRUD_CONTROLLER_FQCN, 'csrfToken', EA::ENTITY_ID, 'fieldName'], array_keys($parameters));
        static::assertSame('active', $parameters['fieldName']);
        static::assertSame((string) $categoryId, $parameters[EA::ENTITY_ID]);

        // the same request that assets/js/field-boolean.js sends when the switch is turned on
        $this->client->request('PATCH', $toggleUrl.'&newValue=true', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        static::assertResponseIsSuccessful();

        $this->entityManager->clear();
        $category = $this->categories->find($categoryId);
        static::assertTrue($category->isActive());
        static::assertSame($this->getSlug($searchQuery.' 2'), $category->getSlug());
        static::assertFalse($this->categories->find($otherCategoryId)->isActive());
    }

    private function getUniqueSearchQuery(): string
    {
        return 'Entity action URL '.bin2hex(random_bytes(4));
    }

    private function getSlug(string $name): string
    {
        return str_replace(' ', '-', strtolower($name));
    }

    private function createCategory(string $name): int
    {
        $category = (new Category())
            ->setName($name)
            ->setSlug($this->getSlug($name))
            ->setActive(false);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        $this->createdCategoryIds[] = $category->getId();

        return $category->getId();
    }

    private function getUnrelatedQueryString(int $categoryId): string
    {
        return http_build_query([
            '_query' => [EA::ENTITY_ID => $categoryId, 'fieldName' => 'slug', 'newValue' => 'true', EA::ROUTE_NAME => 'admin'],
            '_fragment' => 'unrelated',
            'unrelated' => 'value',
        ]);
    }

    private function getRow(Crawler $crawler, int $entityId): Crawler
    {
        return $crawler->filter(sprintf('tr[data-id="%d"]', $entityId));
    }

    /**
     * @return array<string, mixed>
     */
    private function getQueryParameters(string $url): array
    {
        parse_str(parse_url($url, \PHP_URL_QUERY) ?? '', $parameters);
        ksort($parameters);

        return $parameters;
    }
}
