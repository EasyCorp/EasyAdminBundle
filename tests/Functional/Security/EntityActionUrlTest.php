<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\IdPlaceholderDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The URLs that change an entity (the delete button and the boolean switch) must
 * point to the entity of their row, whatever the query string of the current page.
 */
class EntityActionUrlTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

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
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $this->categories = $this->entityManager->getRepository(Category::class);
    }

    #[TestWith([SecuredDashboardController::class])]
    #[TestWith([IdPlaceholderDashboardController::class])]
    public function testDeleteButtonDeletesTheEntityOfItsRow(string $dashboardFqcn): void
    {
        $otherCategoryId = CategoryFactory::createOne(['name' => 'Category 1'])->getId();
        $categoryId = CategoryFactory::createOne(['name' => 'Category 2'])->getId();

        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, $dashboardFqcn).'?'.$this->getUnrelatedQueryString($otherCategoryId));
        $deleteUrl = $this->getRow($crawler, $categoryId)->filter('.action-delete')->attr('formaction');

        static::assertSame($this->getCrudUrl(Action::DELETE, $categoryId, [], $dashboardFqcn), $deleteUrl);
        static::assertSame([], $this->getQueryParameters($deleteUrl));

        $this->client->request('POST', $deleteUrl, ['token' => $crawler->filter('#action-confirmation-form input[name="token"]')->attr('value')]);

        $this->entityManager->clear();
        static::assertNull($this->categories->find($categoryId));
        static::assertNotNull($this->categories->find($otherCategoryId));
    }

    public function testDeleteButtonKeepsTheStateOfTheListing(): void
    {
        $categoryId = CategoryFactory::createOne(['name' => 'Category 1'])->getId();

        $crawler = $this->client->request('GET', $this->generateIndexUrl('Category').'&page=1&sort[name]=ASC');
        $deleteUrl = $this->getRow($crawler, $categoryId)->filter('.action-delete')->attr('formaction');

        static::assertSame(['page' => '1', 'query' => 'Category', 'sort' => ['name' => 'ASC']], $this->getQueryParameters($deleteUrl));
    }

    #[TestWith([SecuredDashboardController::class])]
    #[TestWith([IdPlaceholderDashboardController::class])]
    public function testSwitchTogglesTheFieldAndTheEntityOfItsRow(string $dashboardFqcn): void
    {
        $otherCategoryId = CategoryFactory::createOne(['name' => 'Category 1', 'active' => false, 'slug' => 'category-1'])->getId();
        $categoryId = CategoryFactory::createOne(['name' => 'Category 2', 'active' => false, 'slug' => 'category-2'])->getId();

        $crawler = $this->client->request('GET', $this->generateIndexUrl(null, $dashboardFqcn).'?'.$this->getUnrelatedQueryString($otherCategoryId));
        $toggleUrl = $this->getRow($crawler, $categoryId)->filter('td[data-column="active"] input[type="checkbox"]')->attr('data-toggle-url');

        static::assertNull(parse_url($toggleUrl, \PHP_URL_FRAGMENT));
        static::assertSame(parse_url($this->getCrudUrl(Action::EDIT, $categoryId, [], $dashboardFqcn), \PHP_URL_PATH), parse_url($toggleUrl, \PHP_URL_PATH));
        $parameters = $this->getQueryParameters($toggleUrl);
        static::assertSame(['csrfToken', 'fieldName'], array_keys($parameters));
        static::assertSame('active', $parameters['fieldName']);

        // the same request that assets/js/field-boolean.js sends when the switch is turned on
        $this->client->request('PATCH', $toggleUrl.'&newValue=true', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        static::assertResponseIsSuccessful();

        $this->entityManager->clear();
        $category = $this->categories->find($categoryId);
        static::assertTrue($category->isActive());
        static::assertSame('category-2', $category->getSlug());
        static::assertFalse($this->categories->find($otherCategoryId)->isActive());
    }

    public function testEntityIdIsNotReplacedByTheIdOfTheQueryString(): void
    {
        $otherCategoryId = CategoryFactory::createOne(['name' => 'Category 1'])->getId();
        $categoryId = CategoryFactory::createOne(['name' => 'Category 2'])->getId();

        $editUrl = $this->getCrudUrl(Action::EDIT, $categoryId, [], IdPlaceholderDashboardController::class);
        $crawler = $this->client->request('GET', $editUrl.'?id='.$otherCategoryId);
        $form = $crawler->filter($this->getEntityFormSelector())->form();
        $formValues = $form->getPhpValues();
        $formValues['ea']['newForm']['btn'] = Action::SAVE_AND_CONTINUE;

        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        static::assertResponseRedirects($editUrl);
    }

    private function getUnrelatedQueryString(int $categoryId): string
    {
        return http_build_query([
            'id' => $categoryId,
            '_query' => ['fieldName' => 'slug', 'newValue' => 'true', 'routeName' => 'secure_danger_zone'],
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
