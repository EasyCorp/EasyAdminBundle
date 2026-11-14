<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Security;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\PlaceholderEntityLabelCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\PlaceholderTitleCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\SecuredDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;

/**
 * Tests that the values of the entity placeholders (%entity_as_string%, %entity_id%,
 * etc.) are escaped in the page titles and labels rendered as HTML, including when
 * those placeholders are part of the entity data returned by a closure.
 */
class PlaceholderEscapingTest extends AbstractCrudTestCase
{
    // Category::__toString() returns the name, so the placeholder is replaced by the name itself
    private const CATEGORY_NAME = '<img src=x onerror=alert(1)> & \'Category 1\' %entity_as_string%';
    private const ID_SUFFIX = '<img src=x onerror=alert(1)>';

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return PlaceholderTitleCategoryCrudController::class;
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

    public function testPageTitleWithPlaceholders(): void
    {
        $categoryId = $this->createCategory();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($categoryId));
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
        $expectedTitle = sprintf('Category #%d %s', $categoryId, self::CATEGORY_NAME);
        static::assertSame($expectedTitle, $crawler->filter('h1.title')->text());
        static::assertSame($expectedTitle, $crawler->filter('head title')->text());
    }

    public function testPageTitleHtmlWithPlaceholders(): void
    {
        $categoryId = $this->createCategory();
        $this->client->request('GET', $this->generateDetailUrl($categoryId));
        $html = (string) $this->client->getResponse()->getContent();

        $escapedName = '&lt;img src=x onerror=alert(1)&gt; &amp; &#039;Category 1&#039; %entity_as_string%';

        static::assertResponseIsSuccessful();
        static::assertSame(1, preg_match('#<h1 class="title">(.*?)</h1>#s', $html, $pageTitle));
        static::assertSame(1, preg_match('#<title>(.*?)</title>#s', $html, $documentTitle));
        static::assertSame(sprintf('Category #%d <b>%s</b>', $categoryId, $escapedName), trim($pageTitle[1]));
        // the document title removes the HTML tags written by the developer
        static::assertSame(sprintf('Category #%d %s', $categoryId, $escapedName), trim($documentTitle[1]));
    }

    public function testPageTitleReturnedByClosure(): void
    {
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->createCategory()));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertCount(1, $crawler->filter('h1.title b'));
        static::assertSame('Editing '.$this->expandEntityAsString(self::CATEGORY_NAME), $crawler->filter('h1.title')->text());
    }

    public function testEntityLabelReturnedByClosure(): void
    {
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($this->createCategory(), controllerFqcn: PlaceholderEntityLabelCategoryCrudController::class));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertCount(1, $crawler->filter('h1.title i'));
        static::assertSame('Edit Category '.$this->expandEntityAsString(self::CATEGORY_NAME), $crawler->filter('h1.title')->text());
    }

    public function testActionLabelReturnedByClosure(): void
    {
        $crawler = $this->client->request('GET', $this->generateDetailUrl($this->createCategory()));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.action-archive img'));
        static::assertCount(1, $crawler->filter('.action-archive b'));
        static::assertSame('Archive '.$this->expandEntityAsString(self::CATEGORY_NAME), $crawler->filter('.action-archive .action-label')->text());
    }

    public function testActionLabelsOfActionGroup(): void
    {
        $categoryId = $this->createCategory();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($categoryId));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.action-group img'));
        static::assertSame('Publish '.$this->expandEntityAsString(self::CATEGORY_NAME), $crawler->filter('.action-group .action-publish')->text());
        static::assertSame('Hide '.$this->expandEntityAsString(self::CATEGORY_NAME), $crawler->filter('.action-group .action-hide')->text());
        static::assertSame(sprintf('Lock #%d', $categoryId), $crawler->filter('.action-group .action-lock')->text());
        static::assertCount(1, $crawler->filter('.action-group .action-lock b'));
    }

    public function testActionLabelsOfIndexPageWithEntityIdInQueryString(): void
    {
        $categoryId = $this->createCategory();
        $crawler = $this->client->request('GET', $this->generateIndexUrl(), ['entityId' => $categoryId]);

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('.action-archive img'));
        static::assertSame('Archive '.$this->expandEntityAsString(self::CATEGORY_NAME), $crawler->filter('.action-archive .action-label')->text());
    }

    public function testEntityIdPlaceholderInEntityData(): void
    {
        $this->skipUnlessTheDatabaseLoadsNonCanonicalIds();

        $categoryId = CategoryFactory::createOne(['name' => 'Category %entity_id%'])->getId();
        $crawler = $this->client->request('GET', $this->generateEditFormUrl($categoryId.self::ID_SUFFIX));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertSame(sprintf('Editing Category %d', $categoryId), $crawler->filter('h1.title')->text());
    }

    public function testConfirmationMessageIsNotEscapedTwice(): void
    {
        $categoryId = $this->createCategory();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($categoryId));

        static::assertResponseIsSuccessful();
        // these values are displayed as plain text, so they are only escaped by Twig when rendering the HTML attributes
        $action = $crawler->filter('.action-archive');
        static::assertSame(sprintf('Archive %s (#%d)?', self::CATEGORY_NAME, $categoryId), $action->attr('data-action-confirmation-message'));
        static::assertSame('Yes, archive '.self::CATEGORY_NAME, $action->attr('data-action-confirmation-button'));
    }

    public function testEntityIdOfThePageTitleComesFromTheEntity(): void
    {
        $this->skipUnlessTheDatabaseLoadsNonCanonicalIds();

        $categoryId = $this->createCategory();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($categoryId.self::ID_SUFFIX));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title img'));
        static::assertSame(sprintf('Category #%d %s', $categoryId, self::CATEGORY_NAME), $crawler->filter('h1.title')->text());
        static::assertCount(0, $crawler->filter('.action-archive img'));
    }

    public function testEntityIdOfTheDefaultPageTitleComesFromTheEntity(): void
    {
        $this->skipUnlessTheDatabaseLoadsNonCanonicalIds();

        $categoryId = $this->createCategory();
        $crawler = $this->client->request('GET', $this->generateDetailUrl($categoryId.'<i>', controllerFqcn: CategoryCrudController::class));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('h1.title i'));
        static::assertSame(sprintf('(#%d)', $categoryId), $crawler->filter('h1.title small')->text());
    }

    private function createCategory(): int
    {
        return CategoryFactory::createOne(['name' => self::CATEGORY_NAME])->getId();
    }

    // the placeholder inside the value returned by a closure is replaced too, but only once
    private function expandEntityAsString(string $text): string
    {
        return strtr($text, ['%entity_as_string%' => self::CATEGORY_NAME]);
    }

    // SQLite casts '1<...>' to the integer 1 when looking for the entity, so the page is
    // displayed for an ID that is not the canonical one; other databases may reject that ID
    private function skipUnlessTheDatabaseLoadsNonCanonicalIds(): void
    {
        if (!self::getContainer()->get('doctrine')->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            $this->markTestSkipped('Only SQLite finds entities with IDs like "1<...>".');
        }
    }
}
