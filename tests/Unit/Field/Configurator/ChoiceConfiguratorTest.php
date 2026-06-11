<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Configurator;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ChoiceConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\AbstractFieldTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Fixtures\ChoiceField\PriorityUnitEnum;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Fixtures\ChoiceField\StatusBackedEnum;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Fixtures\ChoiceField\TranslatableStatusBackedEnum;

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

    public function testTranslatableBackedEnumTypeChoices(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME);
        $field->getAsDto()->setDoctrineMetadata(['enumType' => TranslatableStatusBackedEnum::class]);

        // enums implementing TranslatableInterface must be passed to the form as a plain
        // list of cases (not an array keyed by case name) so Symfony's EnumType uses its
        // own translatable labeling instead of the case names as labels (see issue #7242).
        $formChoices = $this->configure($field)->getFormTypeOption('choices');

        $this->assertSame(TranslatableStatusBackedEnum::cases(), $formChoices);
        $this->assertTrue(array_is_list($formChoices));
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

    public function testDefaultColumnsAreAppliedOnAutocompleteWidget(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME);

        $this->assertSame('col-md-6 col-xxl-5', $this->configure($field)->getDefaultColumns());
    }

    public function testDefaultColumnsAreAppliedOnNativeWidget(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME)->renderAsNativeWidget();

        $this->assertSame('col-md-6 col-xxl-5', $this->configure($field)->getDefaultColumns());
    }

    public function testDefaultColumnsAreAppliedOnExpandedWidget(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME)->renderExpanded();

        $this->assertSame('col-md-6 col-xxl-5', $this->configure($field)->getDefaultColumns());
    }

    public function testDefaultColumnsAreWiderOnMultipleNativeWidget(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME)->renderAsNativeWidget()->allowMultipleChoices();

        $this->assertSame('col-md-8 col-xxl-6', $this->configure($field)->getDefaultColumns());
    }

    public function testUserDefinedColumnsAreNotOverridden(): void
    {
        $field = ChoiceField::new(self::PROPERTY_NAME)->renderAsNativeWidget()->setColumns('col-md-4');

        $this->assertSame('col-md-4', $this->configure($field)->getColumns());
    }
}
