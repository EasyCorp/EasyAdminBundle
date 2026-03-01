<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TextConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextareaFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new TextConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = TextareaField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(TextareaField::OPTION_MAX_LENGTH));
        self::assertSame(5, $fieldDto->getCustomOption(TextareaField::OPTION_NUM_OF_ROWS));
        self::assertFalse($fieldDto->getCustomOption(TextareaField::OPTION_RENDER_AS_HTML));
        self::assertFalse($fieldDto->getCustomOption(TextareaField::OPTION_STRIP_TAGS));
        self::assertSame(TextareaType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-textarea', $fieldDto->getCssClass());
    }

    public function testFormTypeOptionsForTextarea(): void
    {
        $field = TextareaField::new('foo');
        $fieldDto = $this->configure($field);

        // textConfigurator sets rows and data attribute for TextareaField via nested attr
        $attr = $fieldDto->getFormTypeOption('attr');
        self::assertIsArray($attr);
        self::assertSame(5, $attr['rows'] ?? null);
        self::assertTrue($attr['data-ea-textarea-field'] ?? false);
    }

    public function testFieldWithNullValue(): void
    {
        $field = TextareaField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithStringValue(): void
    {
        $field = TextareaField::new('foo');
        $field->setValue("Line 1\nLine 2\nLine 3");
        $fieldDto = $this->configure($field);

        self::assertSame("Line 1\nLine 2\nLine 3", $fieldDto->getValue());
    }

    public function testSetNumOfRows(): void
    {
        $field = TextareaField::new('foo');
        $field->setNumOfRows(10);
        $fieldDto = $this->configure($field);

        self::assertSame(10, $fieldDto->getCustomOption(TextareaField::OPTION_NUM_OF_ROWS));
        $attr = $fieldDto->getFormTypeOption('attr');
        self::assertIsArray($attr);
        self::assertSame(10, $attr['rows'] ?? null);
    }

    public function testSetNumOfRowsThrowsExceptionForZeroOrNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextareaField::new('foo')->setNumOfRows(0);
    }

    public function testSetMaxLength(): void
    {
        $field = TextareaField::new('foo');
        $field->setValue('123456789012345');
        $field->setMaxLength(10);
        $fieldDto = $this->configure($field);

        self::assertSame(10, $fieldDto->getCustomOption(TextareaField::OPTION_MAX_LENGTH));
        self::assertLessThanOrEqual(10, mb_strlen($fieldDto->getFormattedValue()));
    }

    public function testSetMaxLengthThrowsExceptionForZeroOrNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextareaField::new('foo')->setMaxLength(0);
    }

    public function testRenderAsHtml(): void
    {
        $htmlContent = '<p>Paragraph 1</p><p>Paragraph 2</p>';
        $field = TextareaField::new('foo');
        $field->setValue($htmlContent);
        $field->renderAsHtml();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(TextareaField::OPTION_RENDER_AS_HTML));
        self::assertSame($htmlContent, $fieldDto->getFormattedValue());
    }

    public function testStripTags(): void
    {
        $htmlContent = '<p>Paragraph 1</p><p>Paragraph 2</p>';
        $field = TextareaField::new('foo');
        $field->setValue($htmlContent);
        $field->stripTags();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(TextareaField::OPTION_STRIP_TAGS));
        self::assertSame('Paragraph 1Paragraph 2', $fieldDto->getFormattedValue());
    }

    public function testFieldTruncatesOnIndexPage(): void
    {
        $longText = str_repeat('a', 100);
        $field = TextareaField::new('foo');
        $field->setValue($longText);
        $fieldDto = $this->configure($field);

        // default max length on index page is 64
        self::assertStringEndsWith('…', $fieldDto->getFormattedValue());
        self::assertLessThanOrEqual(64, mb_strlen($fieldDto->getFormattedValue()));
    }

    public function testFieldDoesNotTruncateOnDetailPage(): void
    {
        $longText = str_repeat('a', 100);
        $field = TextareaField::new('foo');
        $field->setValue($longText);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        self::assertSame($longText, $fieldDto->getFormattedValue());
    }

    public function testHtmlEntitiesAreEscapedOnlyOnce(): void
    {
        $textWithSpecialChars = '<tag> & "quotes"';
        $field = TextareaField::new('foo');
        $field->setValue($textWithSpecialChars);
        $fieldDto = $this->configure($field);

        // when renderAsHtml is false (default), special chars should be escaped once;
        // the formatted value should contain HTML entities like &lt; and &gt;
        self::assertStringContainsString('&lt;', $fieldDto->getFormattedValue());
        self::assertStringContainsString('&gt;', $fieldDto->getFormattedValue());
        self::assertStringContainsString('&amp;', $fieldDto->getFormattedValue());

        // but it should not be double-escaped (no &amp;lt;)
        self::assertStringNotContainsString('&amp;lt;', $fieldDto->getFormattedValue());
        self::assertStringNotContainsString('&amp;gt;', $fieldDto->getFormattedValue());
    }

    /**
     * @dataProvider providePageConfigurations
     */
    public function testTemplateDoesNotDoubleEscape(string $pageName, string $actionName): void
    {
        $textWithSpecialChars = '<tag> & "quotes"';
        $field = TextareaField::new('foo');
        $field->setValue($textWithSpecialChars);
        $fieldDto = $this->configure($field, $pageName, 'en', $actionName);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // the rendered HTML should contain escaped entities once
        self::assertStringContainsString('&lt;', $html, 'Template should contain &lt; (escaped <)');
        self::assertStringContainsString('&gt;', $html, 'Template should contain &gt; (escaped >)');
        self::assertStringContainsString('&amp;', $html, 'Template should contain &amp; (escaped &)');

        // but should NOT contain double-escaped entities
        self::assertStringNotContainsString('&amp;lt;', $html, 'Template should NOT contain &amp;lt; (double-escaped <)');
        self::assertStringNotContainsString('&amp;gt;', $html, 'Template should NOT contain &amp;gt; (double-escaped >)');
        self::assertStringNotContainsString('&amp;amp;', $html, 'Template should NOT contain &amp;amp; (double-escaped &)');
    }

    public static function providePageConfigurations(): \Generator
    {
        yield 'index page' => [Crud::PAGE_INDEX, Action::INDEX];
        yield 'detail page' => [Crud::PAGE_DETAIL, Action::DETAIL];
    }

    public function testTemplatePreservesLineBreaksOnDetailPage(): void
    {
        $textWithLineBreaks = "Line 1\nLine 2\nLine 3";
        $field = TextareaField::new('foo');
        $field->setValue($textWithLineBreaks);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // line breaks should be converted to <br> tags on detail page
        self::assertSame(2, substr_count($html, '<br'), 'Template should convert 2 line breaks to 2 <br> tags on detail page');
    }

    public function testTemplateRendersHtmlWhenRenderAsHtmlIsEnabled(): void
    {
        $htmlContent = '<p>Paragraph 1</p><p>Paragraph 2</p>';
        $field = TextareaField::new('foo');
        $field->setValue($htmlContent);
        $field->renderAsHtml();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // when renderAsHtml is true, HTML tags should be preserved in the span content
        self::assertStringContainsString('<p>Paragraph 1</p>', $html, 'Template should preserve HTML tags when renderAsHtml is enabled');
        self::assertStringContainsString('<p>Paragraph 2</p>', $html, 'Template should preserve HTML tags when renderAsHtml is enabled');

        // the title attribute will always be escaped (which is correct for security),
        // but the span content should have the raw HTML
        // to verify this, we check that the closing span comes after the paragraphs
        self::assertMatchesRegularExpression('/<p>Paragraph 1<\/p><p>Paragraph 2<\/p>.*<\/span>/s', $html, 'HTML content should be rendered inside the span');
    }
}
