<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class HiddenFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // HiddenField has no dedicated configurator
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return HiddenField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // No-op: HiddenField has no special configuration
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = HiddenField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame(HiddenType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-hidden', $fieldDto->getCssClass());
        self::assertSame('crud/field/hidden', $fieldDto->getTemplateName());
    }

    public function testFieldWithValue(): void
    {
        $field = HiddenField::new('foo');
        $field->setValue('hidden_value');
        $fieldDto = $this->configure($field);

        self::assertSame('hidden_value', $fieldDto->getValue());
    }

    public function testFieldWithNullValue(): void
    {
        $field = HiddenField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithIntegerValue(): void
    {
        $field = HiddenField::new('foo');
        $field->setValue(123);
        $fieldDto = $this->configure($field);

        self::assertSame(123, $fieldDto->getValue());
    }
}
