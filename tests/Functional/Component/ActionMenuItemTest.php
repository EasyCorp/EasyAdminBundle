<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Rendering tests for the <twig:ea:ActionMenu:ActionList:Item> component.
 */
class ActionMenuItemTest extends AbstractFieldFunctionalTestCase
{
    private function renderItem(string $template, array $context = []): string
    {
        return static::getContainer()->get('twig')->createTemplate($template)->render($context);
    }

    public function testRegularItemIsRenderedAsLinkInsideListItem(): void
    {
        $html = $this->renderItem('<twig:ea:ActionMenu:ActionList:Item label="Edit" url="/edit" />');

        self::assertStringContainsString('<li>', $html);
        self::assertStringContainsString('href="/edit"', $html);
        self::assertMatchesRegularExpression('/<span\s*>Edit<\/span>/', $html);
        self::assertStringNotContainsString('<form', $html);
    }

    public function testFormItemRendersFormWithGeneratedUniqueId(): void
    {
        $html = $this->renderItem('<twig:ea:ActionMenu:ActionList:Item label="Delete" url="/delete" renderAsForm="true" />');

        self::assertMatchesRegularExpression('/<form action="\/delete" method="POST" id="ea-form-[0-9A-HJKMNP-TV-Z]{26}"><\/form>/', $html);
        self::assertSame(1, preg_match('/id="(ea-form-[^"]+)"/', $html, $matches));
        self::assertStringContainsString(sprintf('data-ea-action-form-id="%s"', $matches[1]), $html);
        self::assertStringContainsString('href="#"', $html);
    }

    #[DataProvider('provideEmptyLabels')]
    public function testEmptyLabelRendersNoSpan(string $labelAttribute): void
    {
        $html = $this->renderItem(sprintf('<twig:ea:ActionMenu:ActionList:Item %s url="/edit" :showBlankIcons="false" />', $labelAttribute));

        self::assertStringNotContainsString('<span', $html);
    }

    public static function provideEmptyLabels(): iterable
    {
        yield 'no label' => [''];
        yield 'empty string label' => ['label=""'];
        yield 'null label' => ['label="{{ null }}"'];
        yield 'false label' => ['label="{{ false }}"'];
    }

    public function testWithoutListItemWrapper(): void
    {
        $html = $this->renderItem('<twig:ea:ActionMenu:ActionList:Item label="Edit" url="/edit" :wrapInListItem="false" />');

        self::assertStringNotContainsString('<li>', $html);
        self::assertStringNotContainsString('</li>', $html);
        self::assertStringContainsString('href="/edit"', $html);
    }

    public function testCustomClassIsMergedWithDefaults(): void
    {
        $html = $this->renderItem('<twig:ea:ActionMenu:ActionList:Item label="Edit" url="/edit" class="my-item" />');

        self::assertStringContainsString('class="dropdown-item my-item"', $html);
    }
}
