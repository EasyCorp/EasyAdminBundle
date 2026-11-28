<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Generator\LabelGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\TextTemplate\NonTextFieldsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\TextTemplate\TextFieldsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 'crud/field/text' is the default template of all fields and any field can use the
 * 'crud/field/textarea' and 'crud/field/choice' templates, so these templates must
 * escape the contents that the configurators of TextField, TextareaField and
 * ChoiceField didn't process.
 */
class TextTemplateEscapingTest extends AbstractCrudTestCase
{
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
        yield ['author.favoriteCategory'];
        yield ['slug'];
        yield ['author.email'];
        yield ['author.address.city'];
        yield ['author.favoriteCategory.name'];
    }

    #[DataProvider('propertiesWithHtmlContents')]
    public function testFieldContentsAreEscapedOnIndexPage(string $property): void
    {
        $this->createBlogPostWithHtmlContents();

        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $cell = $crawler->filter(sprintf('td[data-column="%s"]', $property));
        static::assertCount(1, $cell);
        static::assertStringContainsString('Value 1 <b>bold</b>', $cell->text());
        static::assertCount(0, $cell->filter('b'));
    }

    #[DataProvider('propertiesWithHtmlContents')]
    public function testFieldContentsAreEscapedOnDetailPage(string $property): void
    {
        $blogPost = $this->createBlogPostWithHtmlContents();

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertResponseIsSuccessful();
        $fieldValue = $this->findDetailFieldValue($crawler, $property);
        static::assertStringContainsString('Value 1 <b>bold</b>', $fieldValue->text());
        static::assertCount(0, $fieldValue->filter('b'));
    }

    #[TestWith(['author.name'])]
    #[TestWith(['author.address.city'])]
    public function testNewLinesOfNonTextFieldsAreConvertedOnDetailPage(string $property): void
    {
        $blogPost = BlogPostFactory::createOne([
            'author' => UserFactory::new([
                'name' => "Line 1 <b>bold</b>\nLine 2",
                'address' => (new Address())->setCity("Line 1 <b>bold</b>\nLine 2"),
            ]),
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId()));

        static::assertResponseIsSuccessful();
        $fieldValue = $this->findDetailFieldValue($crawler, $property);
        static::assertStringContainsString('Line 1 &lt;b&gt;bold&lt;/b&gt;<br>', $fieldValue->html());
        static::assertCount(0, $fieldValue->filter('b'));
    }

    public function testNewLinesOfTextFieldsAreConvertedOnDetailPage(): void
    {
        $blogPost = BlogPostFactory::createOne(['title' => "Line 1 <b>bold</b> &amp; more\nLine 2"]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId(), controllerFqcn: TextFieldsCrudController::class));

        static::assertResponseIsSuccessful();
        $fieldValue = $this->findDetailFieldValue($crawler, 'title');
        static::assertStringContainsString('Line 1 &lt;b&gt;bold&lt;/b&gt; &amp; more<br>', $fieldValue->html());
        static::assertCount(0, $fieldValue->filter('b'));
    }

    public function testTextFieldsAreEscapedOnlyOnce(): void
    {
        $blogPost = BlogPostFactory::createOne([
            'title' => 'Title 1 <b>bold</b> &amp; more',
            'slug' => 'Slug 1 <b>bold</b> a < b &amp; c',
            'content' => 'Content 1',
            'author' => UserFactory::new(['name' => 'Name 1', 'email' => 'Email 1']),
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(controllerFqcn: TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        $this->assertTextFieldContents($crawler->filter('td[data-column="title"]'), $crawler->filter('td[data-column="slug"]'));

        $crawler = $this->client->request('GET', $this->generateDetailUrl($blogPost->getId(), controllerFqcn: TextFieldsCrudController::class));
        static::assertResponseIsSuccessful();
        $this->assertTextFieldContents($this->findDetailFieldValue($crawler, 'title'), $this->findDetailFieldValue($crawler, 'slug'));
    }

    public function testRenderAsHtmlAndFormatValueRenderHtmlContents(): void
    {
        $blogPost = BlogPostFactory::createOne([
            'title' => 'Title 1',
            'content' => 'Content 1 <i>italic</i>',
            'author' => UserFactory::new(['name' => 'Name 1 <s>deleted</s>', 'email' => 'Email 1']),
        ]);

        foreach ([$this->generateIndexUrl(controllerFqcn: TextFieldsCrudController::class), $this->generateDetailUrl($blogPost->getId(), controllerFqcn: TextFieldsCrudController::class)] as $url) {
            $crawler = $this->client->request('GET', $url);

            static::assertResponseIsSuccessful();
            $body = $crawler->filter('.content-body');
            static::assertCount(1, $body->filter('i'), 'TextareaField with renderAsHtml()');
            static::assertCount(1, $body->filter('s'), 'TextField with renderAsHtml() on a nested property');
            static::assertCount(1, $body->filter('em'), 'Field with formatValue()');
            static::assertCount(1, $body->filter('u'), 'TextField with formatValue()');
            static::assertCount(1, $body->filter('mark'), 'ChoiceField with formatValue() on a null value');
        }
    }

    public function testFormatValueRendersValuesThatCannotBeConvertedToString(): void
    {
        $blogPost = BlogPostFactory::createOne([
            'title' => 'Title 1',
            'author' => UserFactory::new(['address' => (new Address())->setCity('City 1')]),
        ]);

        foreach ([$this->generateIndexUrl(controllerFqcn: TextFieldsCrudController::class), $this->generateDetailUrl($blogPost->getId(), controllerFqcn: TextFieldsCrudController::class)] as $url) {
            $crawler = $this->client->request('GET', $url);

            static::assertResponseIsSuccessful();
            $formattedValues = $crawler->filter('.content-body span')->reduce(
                static fn (Crawler $span): bool => \in_array(trim($span->text()), ['City: City 1', 'Textarea city: City 1'], true)
            );
            static::assertCount(2, $formattedValues, 'Field with the "text" and "textarea" templates');
            // the value is an object that can't be printed in the "title" attribute
            static::assertNull($formattedValues->eq(0)->attr('title'));
            static::assertNull($formattedValues->eq(1)->attr('title'));
            // the values that can be converted to a string keep their "title" attribute
            static::assertGreaterThan(0, $crawler->filter('.content-body span[title="Title 1"]')->count());
        }
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
        return BlogPostFactory::createOne([
            'title' => 'Value 1 <b>bold</b>',
            'slug' => 'Value 1 <b>bold</b>',
            'content' => 'Value 1 <b>bold</b>',
            'author' => UserFactory::new([
                'name' => 'Value 1 <b>bold</b>',
                'email' => 'Value 1 <b>bold</b>',
                'favoriteCategory' => CategoryFactory::new(['name' => 'Value 1 <b>bold</b>']),
                'address' => (new Address())->setCity('Value 1 <b>bold</b>'),
            ]),
        ]);
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
