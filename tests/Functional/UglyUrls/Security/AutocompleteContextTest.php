<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\UglyUrls\Security;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\AutocompleteContextBlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Kernel;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Same as the AutocompleteContextTest of the secured app, but with the legacy URLs,
 * where the CRUD controller, the action and the entity ID are query parameters.
 *
 * @group legacy
 */
class AutocompleteContextTest extends AbstractCrudTestCase
{
    /** @var list<object> */
    private array $createdEntities = [];

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return AutocompleteContextBlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->persist((new Category())->setName('AcUglyCategory')->setSlug('ac-ugly-category'));
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        foreach (array_reverse($this->createdEntities) as $entity) {
            if (null !== $managedEntity = $this->entityManager->find($entity::class, $entity->getId())) {
                $this->entityManager->remove($managedEntity);
            }
        }
        $this->entityManager->flush();

        parent::tearDown();
    }

    public function testWidgetOfNewPageAppliesTheFieldOptions(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertSame(['Category: AcUglyCategory'], $this->requestAutocompleteResults($this->getAutocompleteUrl($crawler)));
    }

    public function testWidgetOfEditPageAppliesTheFieldOptions(): void
    {
        $author = $this->persist((new User())->setName('AcUglyUser')->setEmail('ac-ugly-user@example.com'));
        $blogPost = $this->persist((new BlogPost())
            ->setTitle('AcUglyBlogPost')
            ->setSlug('ac-ugly-blog-post')
            ->setContent('Lorem ipsum')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($author));

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($blogPost->getId()));
        $autocompleteUrl = $this->getAutocompleteUrl($crawler);

        static::assertStringContainsString(http_build_query(['autocompleteContext' => [EA::ENTITY_ID => (string) $blogPost->getId()]]), $autocompleteUrl);
        static::assertSame(['Category: AcUglyCategory'], $this->requestAutocompleteResults($autocompleteUrl));
    }

    public function testContextWithAClassThatIsNotACrudController(): void
    {
        $this->client->request('GET', $this->getCrudUrl('autocomplete', null, ['query' => 'AcUgly', 'autocompleteContext' => [
            EA::CRUD_CONTROLLER_FQCN => \stdClass::class,
            'propertyName' => 'categories',
            'originatingPage' => 'new',
        ]], controllerFqcn: CategoryCrudController::class));

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return list<string>
     */
    private function requestAutocompleteResults(string $url): array
    {
        $this->client->request('GET', $url.'&'.http_build_query(['query' => 'AcUgly']));
        static::assertResponseIsSuccessful();

        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return array_map(static fn (array $result): string => $result['entityAsString'], $data['results']);
    }

    private function getAutocompleteUrl(Crawler $crawler): string
    {
        static::assertResponseIsSuccessful();
        $widget = $crawler->filter('select[name="BlogPost[categories][autocomplete][]"]');
        static::assertCount(1, $widget);

        return (string) $widget->attr('data-ea-autocomplete-endpoint-url');
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function persist(object $entity): object
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->createdEntities[] = $entity;

        return $entity;
    }
}
