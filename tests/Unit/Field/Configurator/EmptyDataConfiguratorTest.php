<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\EmptyDataConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTest;

class EmptyDataConfiguratorTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new EmptyDataConfigurator();
    }

    /**
     * @dataProvider provideTextBasedFields
     */
    public function testNonNullableTextBasedFieldsUseAnEmptyStringAsEmptyData(FieldInterface $field, string $doctrineType): void
    {
        $fieldDto = $field->getAsDto();
        $fieldDto->setFieldFqcn($field::class);
        $fieldDto->setDoctrineMetadata(['type' => $doctrineType, 'nullable' => false]);

        self::assertTrue($this->configurator->supports($fieldDto, $this->getEntityDto()));
        self::assertSame('', $this->configure($field)->getFormTypeOption('empty_data'));
    }

    public static function provideTextBasedFields(): iterable
    {
        yield 'TextField' => [TextField::new('name'), Types::STRING];
        yield 'TextareaField' => [TextareaField::new('description'), Types::TEXT];
        yield 'TextEditorField' => [TextEditorField::new('content'), Types::TEXT];
        yield 'CodeEditorField' => [CodeEditorField::new('snippet'), Types::TEXT];
        yield 'EmailField' => [EmailField::new('email'), Types::STRING];
        yield 'UrlField' => [UrlField::new('website'), Types::STRING];
        yield 'TelephoneField' => [TelephoneField::new('phone'), Types::STRING];
        yield 'SlugField' => [SlugField::new('slug'), Types::STRING];
        yield 'ColorField' => [ColorField::new('color'), Types::ASCII_STRING];
        yield 'GUID column' => [TextField::new('uuid'), Types::GUID];
    }

    /**
     * Doctrine only stores the 'nullable' value in its metadata cache when it's TRUE, so
     * non-nullable properties have a NULL value for this option in production.
     */
    public function testNonNullableFieldsWithoutCachedNullableValueUseAnEmptyStringAsEmptyData(): void
    {
        $field = TextField::new('name');
        $field->getAsDto()->setDoctrineMetadata(['type' => Types::STRING, 'nullable' => null]);

        self::assertSame('', $this->configure($field)->getFormTypeOption('empty_data'));
    }

    public function testNullableFieldsKeepTheDefaultEmptyData(): void
    {
        $field = TextField::new('name');
        $field->getAsDto()->setDoctrineMetadata(['type' => Types::STRING, 'nullable' => true]);

        self::assertNull($this->configure($field)->getFormTypeOption('empty_data'));
    }

    public function testVirtualFieldsKeepTheDefaultEmptyData(): void
    {
        self::assertNull($this->configure(TextField::new('virtualProperty'))->getFormTypeOption('empty_data'));
    }

    public function testEnumFieldsKeepTheDefaultEmptyData(): void
    {
        $field = TextField::new('status');
        $field->getAsDto()->setDoctrineMetadata(['type' => Types::STRING, 'nullable' => false, 'enumType' => \BackedEnum::class]);

        self::assertNull($this->configure($field)->getFormTypeOption('empty_data'));
    }

    public function testExplicitEmptyDataIsPreserved(): void
    {
        $emptyData = static fn (): null => null;
        $field = TextField::new('name')->setEmptyData($emptyData);
        $field->getAsDto()->setDoctrineMetadata(['type' => Types::STRING, 'nullable' => false]);

        self::assertSame($emptyData, $this->configure($field)->getFormTypeOption('empty_data'));
    }

    public function testExplicitNullEmptyDataIsPreserved(): void
    {
        $field = TextField::new('name')->setEmptyData();
        $field->getAsDto()->setDoctrineMetadata(['type' => Types::STRING, 'nullable' => false]);

        self::assertNull($this->configure($field)->getFormTypeOption('empty_data'));
    }

    public function testFieldsNotBasedOnTextTypeAreNotSupported(): void
    {
        $fieldDto = IntegerField::new('numOfViews')->getAsDto();
        $fieldDto->setFieldFqcn(IntegerField::class);

        self::assertFalse($this->configurator->supports($fieldDto, $this->getEntityDto()));
    }
}
