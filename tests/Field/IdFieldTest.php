<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\IdConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class IdFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new IdConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = IdField::new('id');
        $fieldDto = $this->configure($field);

        self::assertSame(TextType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-id', $fieldDto->getCssClass());
        self::assertSame('crud/field/id', $fieldDto->getTemplateName());
        self::assertNull($fieldDto->getCustomOption(IdField::OPTION_MAX_LENGTH));
    }

    public function testFieldWithNullValue(): void
    {
        $field = IdField::new('id');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
        self::assertNull($fieldDto->getFormattedValue());
    }

    public function testFieldTruncatesValueOnIndexPage(): void
    {
        $field = IdField::new('id');
        $field->setValue('123456789012345');
        $fieldDto = $this->configure($field);

        // default maxLength is 7, and truncate() includes the ellipsis in the count
        self::assertSame('123456…', $fieldDto->getFormattedValue());
    }

    public function testFieldDoesNotTruncateOnDetailPage(): void
    {
        $field = IdField::new('id');
        $field->setValue('123456789012345');
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, 'en', Action::DETAIL);

        self::assertSame('123456789012345', $fieldDto->getValue());
    }

    public function testSetMaxLength(): void
    {
        $field = IdField::new('id');
        $field->setValue('123456789012345');
        $field->setMaxLength(5);
        $fieldDto = $this->configure($field);

        // truncate() includes the ellipsis in the count, so maxLength=5 gives 4 chars + ellipsis
        self::assertSame('1234…', $fieldDto->getFormattedValue());
        self::assertSame(5, $fieldDto->getCustomOption(IdField::OPTION_MAX_LENGTH));
    }

    public function testSetMaxLengthUnlimited(): void
    {
        $field = IdField::new('id');
        $field->setValue('123456789012345');
        $field->setMaxLength(-1);
        $fieldDto = $this->configure($field);

        // -1 means unlimited, so the value should not be truncated
        self::assertSame('123456789012345', $fieldDto->getValue());
    }

    public function testSetMaxLengthThrowsExceptionForZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        IdField::new('id')->setMaxLength(0);
    }
}
