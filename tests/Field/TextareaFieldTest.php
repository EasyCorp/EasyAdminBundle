<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

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

        // TextConfigurator sets rows and data attribute for TextareaField via nested attr
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
}
