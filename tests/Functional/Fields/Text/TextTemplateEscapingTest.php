<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Generator\LabelGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\TextTemplate\NonTextFieldsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\TextTemplate\TextFieldsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 'crud/field/text' is the default template of all fields and any field can use the
 * 'crud/field/textarea' and 'crud/field/choice' templates, so these templates must
 * escape the contents that the configurators of TextField, TextareaField and
 * ChoiceField didn't process.
 */
class TextTemplateEscapingTest extends AbstractCrudTestCase
{
    /** @var array<object> */
    private array $createdEntities = [];

    protected function tearDown(): void
    {
        // the database is shared by all tests, so remove the entities with HTML contents
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        foreach ($this->createdEntities as $createdEntity) {
            if (null !== $entity = $entityManager->find($createdEntity::class, $createdEntity->getId())) {
                $entityManager->remove($entity);
                $entityManager->flush();
            }
        }

        parent::tearDown();
    }

    protected function getControllerFqcn(): string
    {
        return NonTextFieldsCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    /**
     * @return iterable<array{string}>
     */
    public static function propertiesWithHtmlContents(): iterable
    {
        // 'title' is mapped as a string column, so EasyAdmin uses a TextField for it
        yield ['title'];
        yield ['author.name'];
        yield ['displayTitle'];
        yield ['displayAuthor'];
        yield ['slug'];
        yield ['author.email'];
        yield ['publisher.name'];
        yield ['publisher.email'];
    }

    /**
     * @dataProvider propertiesWithHtmlContents
     */
    public function testFieldContentsAreEscapedOnIndexPage(string $property): void
    {
        $blogPost = $this->createBlogPostWithHtmlContents();

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc(NonTextFieldsCrudController::class));

        static::assertResponseIsSuccessful();
        $cell = $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="%s"]', $blogPost->getId(), $property));
        static::assertCount(1, $cell);
        static::assertStringContainsString('Value 1 <b>bold</b>', $cell->text());
        static::assertCount(0, $cell->filter('b'));
    }

    /**
     * @dataProvider propertiesWithHtmlContents
     */
    public function testFieldContentsAreEscapedOnDetailPage(string $property): void
    {
        $blogPost = $this->createBlogPostWithHtmlContents();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertResponseIsSuccessful();
        $fieldValue = $this->findDetailFieldValue($crawler, $property);
        static::assertStringContainsString('Value 1 <b>bold</b>', $fieldValue->text());
        static::assertCount(0, $fieldValue->filter('b'));
    }

    /**
     * @testWith ["author.name"]
     *           ["publisher.name"]
     */
    public function testNewLinesOfNonTextFieldsAreConvertedOnDetailPage(string $property): void
    {
        $user = $this->createUser("Line 1 <b>bold</b>\nLine 2", 'Email 1');
        $blogPost = $this->createBlogPost(['title' => 'Title 1', 'author' => $user, 'publisher' => $user]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertResponseIsSuccessful();
        $fieldValue = $this->findDetailFieldValue($crawler, $property);
        static::assertStringContainsString('Line 1 &lt;b&gt;bold&lt;/b&gt;<br>', $fieldValue->html());
        static::assertCount(0, $fieldValue->filter('b'));
    }

    public function testNewLinesOfTextFieldsAreConvertedOnDetailPage(): void
    {
        $blogPost = $this->createBlogPost(['title' => "Line 1 <b>bold</b> &amp; more\nLine 2"]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId(), null, TextFieldsCrudController::class));

        static::assertResponseIsSuccessful();
        $fieldValue = $this->findDetailFieldValue($crawler, 'title');
        static::assertStringContainsString('Line 1 &lt;b&gt;bold&lt;/b&gt; &amp; more<br>', $fieldValue->html());
        static::assertCount(0, $fieldValue->filter('b'));
    }

    public function testTextFieldsAreEscapedOnlyOnce(): void
    {
        $blogPost = $this->createBlogPost([
            'title' => 'Title 1 <b>bold</b> &amp; more',
            'slug' => 'Slug 1 <b>bold</b> a < b &amp; c',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc(TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        $row = $crawler->filter(sprintf('tr[data-id="%d"]', $blogPost->getId()));
        $this->assertTextFieldContents($row->filter('td[data-column="title"]'), $row->filter('td[data-column="slug"]'));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId(), null, TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        $this->assertTextFieldContents($this->findDetailFieldValue($crawler, 'title'), $this->findDetailFieldValue($crawler, 'slug'));
    }

    public function testRenderAsHtmlAndFormatValueRenderHtmlContents(): void
    {
        $blogPost = $this->createBlogPost([
            'title' => 'Title 1',
            'content' => 'Content 1 <i>italic</i>',
            'author' => $this->createUser('Name 1 <s>deleted</s>', 'Email 1'),
            'publisher' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc(TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        $this->assertHtmlContentsAreRendered($crawler->filter(sprintf('tr[data-id="%d"]', $blogPost->getId())));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId(), null, TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        $this->assertHtmlContentsAreRendered($crawler->filter('.content-body'));
    }

    public function testChoiceLabelsAreRenderedAsHtml(): void
    {
        $user = $this->createUser('Name 1', 'Email 1');
        $blogPost = $this->createBlogPost(['title' => 'Title 1', 'author' => $user, 'publisher' => $user]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc(TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="publisher.name"] span.badge > b', $blogPost->getId())));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId(), null, TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertCount(1, $this->findDetailFieldValue($crawler, 'publisher.name')->filter('span.badge > b'));
    }

    private function assertHtmlContentsAreRendered(Crawler $crawler): void
    {
        static::assertCount(1, $crawler->filter('i'), 'TextareaField with renderAsHtml()');
        static::assertCount(1, $crawler->filter('s'), 'TextField with renderAsHtml() on a nested property');
        static::assertCount(1, $crawler->filter('em'), 'Field with formatValue()');
        static::assertCount(1, $crawler->filter('u'), 'TextField with formatValue()');
        static::assertCount(1, $crawler->filter('mark'), 'ChoiceField with formatValue() on a null value');
    }

    private function assertTextFieldContents(Crawler $title, Crawler $slug): void
    {
        static::assertSame('Title 1 <b>bold</b> & more', trim($title->text()));
        static::assertCount(0, $title->filter('b'));
        static::assertCount(0, $slug->filter('b'));
        // checked in the raw response because HTML parsers disagree about a lone '<' character
        static::assertStringContainsString('Slug 1 bold a < b &amp; c', (string) $this->client->getResponse()->getContent());
    }

    private function createBlogPostWithHtmlContents(): BlogPost
    {
        $user = $this->createUser('Value 1 <b>bold</b>', 'Value 1 <b>bold</b>');

        return $this->createBlogPost([
            'title' => 'Value 1 <b>bold</b>',
            'slug' => 'Value 1 <b>bold</b>',
            'content' => 'Value 1 <b>bold</b>',
            'author' => $user,
            'publisher' => $user,
        ]);
    }

    /**
     * @param array{title?: string, slug?: string, content?: string, author?: User, publisher?: User|null} $values
     */
    private function createBlogPost(array $values): BlogPost
    {
        $blogPost = (new BlogPost())
            ->setTitle($values['title'] ?? 'Title 1')
            ->setSlug($values['slug'] ?? 'slug-1')
            ->setContent($values['content'] ?? 'Content 1')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAuthor($values['author'] ?? $this->createUser('Name 1', 'Email 1'));
        $blogPost->setPublisher($values['publisher'] ?? null);

        $this->entityManager->persist($blogPost);
        $this->entityManager->flush();
        // blog posts must be removed before their users
        array_unshift($this->createdEntities, $blogPost);

        return $blogPost;
    }

    private function createUser(string $name, string $email): User
    {
        $user = (new User())->setName($name)->setEmail($email);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->createdEntities[] = $user;

        return $user;
    }

    private function generateIndexUrlSortedByIdDesc(string $controllerFqcn): string
    {
        return $this->getCrudUrl('index', null, ['sort' => ['id' => 'DESC']], null, $controllerFqcn);
    }

    private function findDetailFieldValue(Crawler $crawler, string $property): Crawler
    {
        $label = LabelGenerator::humanize($property);
        $fieldGroup = $crawler->filter('.content-body .field-group')->reduce(
            static fn (Crawler $group): bool => $label === trim($group->filter('.field-label')->text())
        );
        static::assertCount(1, $fieldGroup, sprintf('The detail page displays the "%s" field.', $property));

        return $fieldGroup->filter('.field-value');
    }
}
