<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\PlaceholderEntityLabelUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\PlaceholderTitleUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;

/**
 * Tests that the values of the entity placeholders (%entity_as_string%, %entity_id%,
 * etc.) are escaped in the page titles and labels rendered as HTML, including when
 * those placeholders are part of the entity data returned by a closure.
 */
class PlaceholderEscapingTest extends AbstractCrudTestCase
{
    // User::__toString() returns the name, so the placeholder is replaced by the name itself
    private const USER_NAME = '<img src=x onerror=alert(1)> & \'User 1\' %entity_as_string%';
    private const ID_SUFFIX = '<img src=x onerror=alert(1)>';

    /** @var list<object> */
    private array $createdEntities = [];

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return PlaceholderTitleUserCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return SecuredDashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);
    }

    protected function tearDown(): void
    {
        if ([] !== $this->createdEntities) {
            $this->entityManager->clear();
            foreach ($this->createdEntities as $entity) {
                $this->entityManager->remove($this->entityManager->find($entity::class, $entity->getId()));
            }
            $this->entityManager->flush();
        }

        parent::tearDown();
    }

    public function testPageTitleWithPlaceholders(): void
    {
        $userId = $this->createUser();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($userId));
        $html = (string) $this->client->getResponse()->getContent();

        static::assertResponseIsSuccessful();

        // the HTML of the entity name is printed as text...
        static::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        static::assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        static::assertCount(0, $crawler->filter('h1.title img'));

        // ...but the HTML that the developer wrote in the title still works
        static::assertCount(1, $crawler->filter('h1.title b'));

        // the name is shown as stored: it's escaped only once and the placeholder
        // that it contains is not replaced again
        $expectedTitle = sprintf('User #%d %s', $userId, self::USER_NAME);
        static::assertSame($expectedTitle, trim($crawler->filter('h1.title')->text()));
        static::assertSame($expectedTitle, trim($crawler->filter('head title')->text()));
    }

    public function testPageTitleHtmlWithPlaceholders(): void
    {
        $userId = $this->createUser();
        $this->client->request('GET', $this->generateDetailUrl($userId));
        $html = (string) $this->client->getResponse()->getContent();

        $escapedName = '&lt;img src=x onerror=alert(1)&gt; &amp; &#039;User 1&#039; %entity_as_string%';

        static::assertResponseIsSuccessful();
        static::assertSame(1, preg_match('#<h1 class="title">(.*?)</h1>#s', $html, $pageTitle));
        static::assertSame(1, preg_match('#<title>(.*?)</title>#s', $html, $documentTitle));
        static::assertSame(sprintf('User #%d <b>%s</b>', $userId, $escapedName), trim($pageTitle[1]));
        // the document title removes the HTML tags written by the developer
        static::assertSame(sprintf('User #%d %s', $userId, $escapedName), trim($documentTitle[1]));
    }

    public function testPageTitleReturnedByClosure(): void
    {
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->createUser()));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertCount(1, $crawler->filter('h1.title b'));
        static::assertSame('Editing '.$this->expandEntityAsString(self::USER_NAME), trim($crawler->filter('h1.title')->text()));
    }

    public function testEntityLabelReturnedByClosure(): void
    {
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->createUser(), controllerFqcn: PlaceholderEntityLabelUserCrudController::class));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertCount(1, $crawler->filter('h1.title i'));
        static::assertSame('Edit User '.$this->expandEntityAsString(self::USER_NAME), trim($crawler->filter('h1.title')->text()));
    }

    public function testActionLabelReturnedByClosure(): void
    {
        $crawler = $this->client->request('GET', $this->generateDetailUrl($this->createUser()));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.action-archive img'));
        static::assertCount(1, $crawler->filter('.action-archive b'));
        static::assertSame('Archive '.$this->expandEntityAsString(self::USER_NAME), $crawler->filter('.action-archive .action-label')->text());
    }

    public function testActionLabelsOfActionGroup(): void
    {
        $userId = $this->createUser();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($userId));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.action-group img'));
        static::assertSame('Publish '.$this->expandEntityAsString(self::USER_NAME), trim($crawler->filter('.action-group [data-action-name="publish"]')->text()));
        static::assertSame('Hide '.$this->expandEntityAsString(self::USER_NAME), trim($crawler->filter('.action-group [data-action-name="hide"]')->text()));
        static::assertSame(sprintf('Lock #%d', $userId), trim($crawler->filter('.action-group [data-action-name="lock"]')->text()));
        static::assertCount(1, $crawler->filter('.action-group [data-action-name="lock"] b'));
    }

    public function testActionLabelsOfIndexPageWithEntityIdInQueryString(): void
    {
        $userId = $this->createUser();
        $crawler = $this->client->request('GET', $this->generateIndexUrl(), ['entityId' => $userId]);

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.action-archive img'));
        static::assertSame('Archive '.$this->expandEntityAsString(self::USER_NAME), $crawler->filter($this->getIndexEntityRowSelector($userId).' .action-archive .action-label')->text());
    }

    public function testEntityIdPlaceholderInEntityData(): void
    {
        $this->skipUnlessTheDatabaseLoadsNonCanonicalIds();

        $userId = $this->createUser('User %entity_id%');
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($userId.self::ID_SUFFIX));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertSame(sprintf('Editing User %d', $userId), trim($crawler->filter('h1.title')->text()));
    }

    public function testConfirmationMessageIsNotEscapedTwice(): void
    {
        $userId = $this->createUser();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($userId));

        static::assertResponseIsSuccessful();
        // these values are displayed as plain text, so they are only escaped by Twig when rendering the HTML attributes
        $action = $crawler->filter('.action-archive');
        static::assertSame(sprintf('Archive %s (#%d)?', self::USER_NAME, $userId), $action->attr('data-action-confirmation-message'));
        static::assertSame('Yes, archive '.self::USER_NAME, $action->attr('data-action-confirmation-button'));
    }

    public function testEntityIdOfThePageTitleComesFromTheEntity(): void
    {
        $this->skipUnlessTheDatabaseLoadsNonCanonicalIds();

        $userId = $this->createUser();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($userId.self::ID_SUFFIX));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertSame(sprintf('User #%d %s', $userId, self::USER_NAME), trim($crawler->filter('h1.title')->text()));
        static::assertCount(0, $crawler->filter('.action-archive img'));
    }

    public function testEntityIdOfTheDefaultPageTitleComesFromTheEntity(): void
    {
        $this->skipUnlessTheDatabaseLoadsNonCanonicalIds();

        $category = (new Category())->setName('Placeholder escaping category')->setSlug('placeholder-escaping-category');
        $this->persist($category);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($category->getId().'<i>', controllerFqcn: CategoryCrudController::class));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title i'));
        static::assertSame(sprintf('(#%d)', $category->getId()), $crawler->filter('h1.title small')->text());
    }

    private function createUser(string $name = self::USER_NAME): int
    {
        $user = (new User())->setName($name)->setEmail('placeholder-escaping@example.com');
        $this->persist($user);

        return $user->getId();
    }

    private function persist(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->createdEntities[] = $entity;
    }

    // the placeholder inside the value returned by a closure is replaced too, but only once
    private function expandEntityAsString(string $text): string
    {
        return strtr($text, ['%entity_as_string%' => self::USER_NAME]);
    }

    // SQLite casts '1<...>' to the integer 1 when looking for the entity, so the page is
    // displayed for an ID that is not the canonical one; other databases may reject that ID
    private function skipUnlessTheDatabaseLoadsNonCanonicalIds(): void
    {
        if (!str_contains(strtolower($this->entityManager->getConnection()->getDatabasePlatform()::class), 'sqlite')) {
            $this->markTestSkipped('Only SQLite finds entities with IDs like "1<...>".');
        }
    }
}
