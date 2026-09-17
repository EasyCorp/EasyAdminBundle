<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TextConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTest;

class TextConfiguratorTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new TextConfigurator();
    }

    /**
     * @dataProvider provideNonNullableDoctrineTextFields
     */
    public function testNonNullableDoctrineTextFieldsUseEmptyStringAsEmptyData(FieldInterface $field, string $doctrineType): void
    {
        $field->getAsDto()->setDoctrineMetadata(['type' => $doctrineType, 'nullable' => false]);

        $fieldDto = $this->configure($field);

        self::assertSame('', $fieldDto->getFormTypeOption('empty_data'));
    }

    public static function provideNonNullableDoctrineTextFields(): iterable
    {
        yield 'string TextField' => [TextField::new('name'), Types::STRING];
        yield 'text TextareaField' => [TextareaField::new('description'), Types::TEXT];
    }

    public function testNullableDoctrineTextFieldKeepsDefaultEmptyData(): void
    {
        $field = TextField::new('name');
        $field->getAsDto()->setDoctrineMetadata(['type' => Types::STRING, 'nullable' => true]);

        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getFormTypeOption('empty_data'));
    }

    public function testExplicitEmptyDataOptionIsPreserved(): void
    {
        $emptyData = static fn (): null => null;
        $field = TextField::new('name')->setFormTypeOption('empty_data', $emptyData);
        $field->getAsDto()->setDoctrineMetadata(['type' => Types::STRING, 'nullable' => false]);

        $fieldDto = $this->configure($field);

        self::assertSame($emptyData, $fieldDto->getFormTypeOption('empty_data'));
    }
}
