<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TextConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TextFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new TextConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = TextField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(TextField::OPTION_MAX_LENGTH));
        self::assertFalse($fieldDto->getCustomOption(TextField::OPTION_RENDER_AS_HTML));
        self::assertFalse($fieldDto->getCustomOption(TextField::OPTION_STRIP_TAGS));
        self::assertSame(TextType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-text', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = TextField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithStringValue(): void
    {
        $field = TextField::new('foo');
        $field->setValue('Hello World');
        $fieldDto = $this->configure($field);

        self::assertSame('Hello World', $fieldDto->getValue());
    }

    public function testFieldTruncatesOnIndexPage(): void
    {
        $longText = str_repeat('a', 100);
        $field = TextField::new('foo');
        $field->setValue($longText);
        $fieldDto = $this->configure($field);

        // default max length on index page is 64
        self::assertStringEndsWith('…', $fieldDto->getFormattedValue());
        self::assertLessThanOrEqual(64, mb_strlen($fieldDto->getFormattedValue()));
    }

    public function testFieldDoesNotTruncateOnDetailPage(): void
    {
        $longText = str_repeat('a', 100);
        $field = TextField::new('foo');
        $field->setValue($longText);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        // on detail page, max length is PHP_INT_MAX (effectively no truncation)
        self::assertSame($longText, $fieldDto->getFormattedValue());
    }

    public function testSetMaxLength(): void
    {
        $field = TextField::new('foo');
        $field->setValue('123456789012345');
        $field->setMaxLength(10);
        $fieldDto = $this->configure($field);

        self::assertSame(10, $fieldDto->getCustomOption(TextField::OPTION_MAX_LENGTH));
        self::assertLessThanOrEqual(10, mb_strlen($fieldDto->getFormattedValue()));
    }

    public function testSetMaxLengthThrowsExceptionForZeroOrNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextField::new('foo')->setMaxLength(0);
    }

    public function testRenderAsHtml(): void
    {
        $htmlContent = '<strong>Bold</strong> and <em>italic</em>';
        $field = TextField::new('foo');
        $field->setValue($htmlContent);
        $field->renderAsHtml();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(TextField::OPTION_RENDER_AS_HTML));
        // HTML content should be preserved, not escaped
        self::assertSame($htmlContent, $fieldDto->getFormattedValue());
    }

    public function testRenderAsHtmlIgnoresMaxLength(): void
    {
        $htmlContent = '<strong>'.str_repeat('a', 100).'</strong>';
        $field = TextField::new('foo');
        $field->setValue($htmlContent);
        $field->renderAsHtml();
        $field->setMaxLength(10);
        $fieldDto = $this->configure($field);

        // when renderAsHtml is true, maxLength is ignored
        self::assertSame($htmlContent, $fieldDto->getFormattedValue());
    }

    public function testStripTags(): void
    {
        $htmlContent = '<strong>Bold</strong> and <em>italic</em>';
        $field = TextField::new('foo');
        $field->setValue($htmlContent);
        $field->stripTags();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(TextField::OPTION_STRIP_TAGS));
        self::assertSame('Bold and italic', $fieldDto->getFormattedValue());
    }

    public function testHtmlEntitiesAreEscapedByDefault(): void
    {
        $field = TextField::new('foo');
        $field->setValue('<script>alert("xss")</script>');
        $fieldDto = $this->configure($field);

        // HTML should be escaped by default
        self::assertStringContainsString('&lt;script&gt;', $fieldDto->getFormattedValue());
    }

    public function testBackedEnumValue(): void
    {
        // create a backed enum for testing
        $enumValue = Fixtures\ChoiceField\StatusBackedEnum::Draft;
        $field = TextField::new('foo');
        $field->setValue($enumValue);
        $fieldDto = $this->configure($field);

        // BackedEnum should be converted to its value
        self::assertSame('draft', $fieldDto->getFormattedValue());
    }
}
