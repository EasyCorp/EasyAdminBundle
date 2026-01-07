<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextEditorType;

class TextEditorFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // TextEditorField has no dedicated configurator
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return TextEditorField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // No-op: TextEditorField has no special configuration logic
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = TextEditorField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(TextEditorField::OPTION_NUM_OF_ROWS));
        self::assertNull($fieldDto->getCustomOption(TextEditorField::OPTION_TRIX_EDITOR_CONFIG));
        self::assertSame(TextEditorType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-text_editor', $fieldDto->getCssClass());
        self::assertSame('crud/field/text_editor', $fieldDto->getTemplateName());
    }

    public function testFieldWithNullValue(): void
    {
        $field = TextEditorField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithHtmlValue(): void
    {
        $htmlContent = '<p>This is <strong>rich</strong> text content.</p>';
        $field = TextEditorField::new('foo');
        $field->setValue($htmlContent);
        $fieldDto = $this->configure($field);

        self::assertSame($htmlContent, $fieldDto->getValue());
    }

    public function testSetNumOfRows(): void
    {
        $field = TextEditorField::new('foo');
        $field->setNumOfRows(15);
        $fieldDto = $this->configure($field);

        self::assertSame(15, $fieldDto->getCustomOption(TextEditorField::OPTION_NUM_OF_ROWS));
    }

    public function testSetNumOfRowsThrowsExceptionForZeroOrNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TextEditorField::new('foo')->setNumOfRows(0);
    }

    public function testSetTrixEditorConfig(): void
    {
        $config = [
            'blockAttributes' => [
                'default' => ['tagName' => 'p'],
            ],
        ];
        $field = TextEditorField::new('foo');
        $field->setTrixEditorConfig($config);
        $fieldDto = $this->configure($field);

        self::assertSame($config, $fieldDto->getCustomOption(TextEditorField::OPTION_TRIX_EDITOR_CONFIG));
    }

    public function testSetTrixEditorConfigWithEmptyArray(): void
    {
        $field = TextEditorField::new('foo');
        $field->setTrixEditorConfig([]);
        $fieldDto = $this->configure($field);

        self::assertSame([], $fieldDto->getCustomOption(TextEditorField::OPTION_TRIX_EDITOR_CONFIG));
    }
}
