<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ChoiceFilter implements FilterInterface
{
    use ChoiceFilterApplyTrait;
    use FilterTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
    }

    /**
     * @param array<mixed> $choices
     */
    public function setChoices(array $choices): self
    {
        $this->dto->setFormTypeOption('value_type_options.choices', $choices);

        return $this;
    }

    /**
     * @param array<string|TranslatableInterface|\UnitEnum> $choiceGenerator
     */
    public function setTranslatableChoices(array $choiceGenerator): self
    {
        // support passing a list of enum cases (e.g. MyEnum::cases()) when the
        // enum implements TranslatableInterface. In that case, the submitted
        // value must be the enum backing value (or its name for UnitEnum),
        // otherwise the keys (0, 1, 2, ...) of the list would be submitted
        // and the query would never match any row.
        if (array_is_list($choiceGenerator) && [] !== $choiceGenerator && $this->areAllEnums($choiceGenerator)) {
            $choices = [];
            foreach ($choiceGenerator as $case) {
                $key = $case instanceof \BackedEnum ? $case->value : $case->name;
                $choices[$key] = $case;
            }
            $choiceGenerator = $choices;
        }

        $this->dto->setFormTypeOption('value_type_options.choices', array_keys($choiceGenerator));
        $this->dto->setFormTypeOption('value_type_options.choice_label', static fn ($value) => $choiceGenerator[$value]);

        return $this;
    }

    /**
     * @param array<mixed> $values
     */
    private function areAllEnums(array $values): bool
    {
        foreach ($values as $value) {
            if (!$value instanceof \UnitEnum) {
                return false;
            }
        }

        return true;
    }

    public function renderExpanded(bool $isExpanded = true): self
    {
        $this->dto->setFormTypeOption('value_type_options.expanded', $isExpanded);

        return $this;
    }

    public function canSelectMultiple(bool $selectMultiple = true): self
    {
        $this->dto->setFormTypeOption('value_type_options.multiple', $selectMultiple);

        return $this;
    }
}
