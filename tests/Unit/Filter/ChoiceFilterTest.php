<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures\BackedEnumChoice;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures\TranslatableBackedEnumChoice;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures\TranslatableUnitEnumChoice;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures\UnitEnumChoice;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;

class ChoiceFilterTest extends TestCase
{
    public function testSetTranslatableChoicesWithTranslatableMessages(): void
    {
        $filter = ChoiceFilter::new('status')->setTranslatableChoices([
            'open' => new TranslatableMessage('Open'),
            'closed' => new TranslatableMessage('Closed'),
        ]);
        $dto = $filter->getAsDto();

        $this->assertSame(['open', 'closed'], $dto->getFormTypeOption('value_type_options.choices'));

        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertIsCallable($choiceLabel);
        $this->assertInstanceOf(TranslatableMessage::class, $choiceLabel('open'));
        $this->assertInstanceOf(TranslatableMessage::class, $choiceLabel('closed'));
    }

    public function testSetTranslatableChoicesWithTranslatableBackedEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setTranslatableChoices(TranslatableBackedEnumChoice::cases());
        $dto = $filter->getAsDto();

        // the submitted values must be the enum backing values, not the list indices (0, 1, ...)
        $this->assertSame(['draft', 'published'], $dto->getFormTypeOption('value_type_options.choices'));

        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertIsCallable($choiceLabel);
        $this->assertSame(TranslatableBackedEnumChoice::Draft, $choiceLabel('draft'));
        $this->assertSame(TranslatableBackedEnumChoice::Published, $choiceLabel('published'));
    }

    public function testSetTranslatableChoicesWithBackedEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setTranslatableChoices(BackedEnumChoice::cases());
        $dto = $filter->getAsDto();

        $this->assertSame(['draft', 'published'], $dto->getFormTypeOption('value_type_options.choices'));

        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertSame(BackedEnumChoice::Draft, $choiceLabel('draft'));
    }

    public function testSetTranslatableChoicesWithTranslatableUnitEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setTranslatableChoices(TranslatableUnitEnumChoice::cases());
        $dto = $filter->getAsDto();

        $this->assertSame(['Draft', 'Published'], $dto->getFormTypeOption('value_type_options.choices'));

        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertSame(TranslatableUnitEnumChoice::Draft, $choiceLabel('Draft'));
    }

    public function testSetChoicesWithLabelValueArray(): void
    {
        $filter = ChoiceFilter::new('status')->setChoices([
            'Draft' => 'draft',
            'Published' => 'published',
        ]);

        $dto = $filter->getAsDto();

        // classic "label => value" arrays are passed through untouched
        $this->assertSame(
            ['Draft' => 'draft', 'Published' => 'published'],
            $dto->getFormTypeOption('value_type_options.choices')
        );
    }

    public function testSetChoicesWithBackedEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setChoices(BackedEnumChoice::cases());

        $dto = $filter->getAsDto();

        // the submitted values must be the enum backing values, not the list indices (0, 1, ...)
        $this->assertSame(['draft', 'published'], $dto->getFormTypeOption('value_type_options.choices'));

        // the enum is not translatable, so the label is the case name (same as ChoiceField)
        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertIsCallable($choiceLabel);
        $this->assertSame('Draft', $choiceLabel('draft'));
        $this->assertSame('Published', $choiceLabel('published'));
    }

    public function testSetChoicesWithUnitEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setChoices(UnitEnumChoice::cases());

        $dto = $filter->getAsDto();

        $this->assertSame(['Draft', 'Published'], $dto->getFormTypeOption('value_type_options.choices'));

        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertIsCallable($choiceLabel);
        $this->assertSame('Draft', $choiceLabel('Draft'));
        $this->assertSame('Published', $choiceLabel('Published'));
    }

    public function testSetChoicesWithTranslatableBackedEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setChoices(TranslatableBackedEnumChoice::cases());

        $dto = $filter->getAsDto();

        $this->assertSame(['draft', 'published'], $dto->getFormTypeOption('value_type_options.choices'));

        // the enum is translatable, so the label is the case itself (translated later by the form)
        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertIsCallable($choiceLabel);
        $this->assertSame(TranslatableBackedEnumChoice::Draft, $choiceLabel('draft'));
        $this->assertSame(TranslatableBackedEnumChoice::Published, $choiceLabel('published'));
    }

    public function testSetChoicesWithTranslatableUnitEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setChoices(TranslatableUnitEnumChoice::cases());

        $dto = $filter->getAsDto();

        $this->assertSame(['Draft', 'Published'], $dto->getFormTypeOption('value_type_options.choices'));

        $choiceLabel = $dto->getFormTypeOption('value_type_options.choice_label');
        $this->assertSame(TranslatableUnitEnumChoice::Draft, $choiceLabel('Draft'));
    }

    public function testSetChoicesWithSubsetOfEnumCases(): void
    {
        $filter = ChoiceFilter::new('status')->setChoices([BackedEnumChoice::Published]);

        $dto = $filter->getAsDto();

        $this->assertSame(['published'], $dto->getFormTypeOption('value_type_options.choices'));
        $this->assertSame('Published', $dto->getFormTypeOption('value_type_options.choice_label')('published'));
    }

    public function testSetChoicesWithEnumValuesInAssociativeArrayIsNotNormalized(): void
    {
        $choices = ['My draft' => BackedEnumChoice::Draft];

        $filter = ChoiceFilter::new('status')->setChoices($choices);

        $dto = $filter->getAsDto();

        // an associative array is not a list of cases, so the user's labels are kept as they are
        $this->assertSame($choices, $dto->getFormTypeOption('value_type_options.choices'));
    }
}
