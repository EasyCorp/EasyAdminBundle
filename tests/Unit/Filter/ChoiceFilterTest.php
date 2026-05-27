<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures\BackedEnumChoice;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures\TranslatableBackedEnumChoice;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures\TranslatableUnitEnumChoice;
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
}
