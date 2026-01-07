<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ChoiceConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Field\AbstractFieldTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Fixtures\ChoiceField\PriorityUnitEnum;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Fixtures\ChoiceField\StatusBackedEnum;

class ChoiceConfiguratorTest extends AbstractFieldTest
{
    private const ENTITY_CLASS = 'AppTestBundle\Entity\UnitTests\Category';
    private const PROPERTY_NAME = 'foo';

    private ?EntityDto $entity = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new ChoiceConfigurator();

        $metadata = new ClassMetadata(self::ENTITY_CLASS);
        $metadata->setIdentifier(['id']);
        $this->entity = new EntityDto(self::ENTITY_CLASS, $metadata);
    }

    /**
     * @dataProvider fieldTypes
     */
    public function testSupportsField(string $fieldType, bool $expectedResult): void
    {
        $field = new FieldDto();
        $field->setFieldFqcn($fieldType);

        $this->assertSame($this->configurator->supports($field, $this->entity), $expectedResult);
    }

    public function testBackedEnumTypeChoices(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME);
        $field->getAsDto()->setDoctrineMetadata(['enumType' => StatusBackedEnum::class]);

        $formChoices = array_combine(
            array_column(StatusBackedEnum::cases(), 'name'),
            StatusBackedEnum::cases(),
        );

        $this->assertSame($this->configure($field)->getFormTypeOption('choices'), $formChoices);
    }

    public function testBackedEnumChoices(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME);
        $field->setCustomOptions(['choices' => StatusBackedEnum::cases()]);

        $expected = [];
        foreach (StatusBackedEnum::cases() as $case) {
            $expected[$case->name] = $case;
        }

        $this->assertSame($this->configure($field)->getFormTypeOption('choices'), $expected);
    }

    public function testUnitEnumTypeChoices(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME);
        $field->getAsDto()->setDoctrineMetadata(['enumType' => PriorityUnitEnum::class]);

        $formChoices = array_combine(
            array_column(PriorityUnitEnum::cases(), 'name'),
            PriorityUnitEnum::cases(),
        );

        $this->assertSame($this->configure($field)->getFormTypeOption('choices'), $formChoices);
    }

    public function testUnitEnumChoices(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME);
        $field->setCustomOptions(['choices' => PriorityUnitEnum::cases()]);

        $expected = [];
        foreach (PriorityUnitEnum::cases() as $case) {
            $expected[$case->name] = $case;
        }

        $this->assertSame($this->configure($field)->getFormTypeOption('choices'), $expected);
    }

    public static function fieldTypes(): iterable
    {
        yield [ChoiceField::class, true];
        yield [TextField::class, false];
        yield [IdField::class, false];
    }

    public function testBackedEnumChoicesLabeled(): void
    {
        $choices = [];
        foreach (StatusBackedEnum::cases() as $case) {
            $choices[$case->label()] = $case;
        }

        $field = ChoiceField::new(self::PROPERTY_NAME);
        $field->setCustomOptions(['choices' => $choices]);

        $this->assertSame($choices, $this->configure($field)->getFormTypeOption('choices'));
    }
}
