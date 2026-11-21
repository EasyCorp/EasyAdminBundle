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
     * @param array<mixed>|list<\UnitEnum> $choices Either a "label => value" array
     *                                              or a list of enum cases (e.g. MyEnum::cases())
     */
    public function setChoices(array $choices): self
    {
        if ($this->isEnumList($choices)) {
            // same behavior as ChoiceField: the submitted value is the enum backing
            // value (or the case name for UnitEnum); the label is the case itself if
            // it is translatable, or the case name otherwise
            $cases = $this->indexEnumCasesByValue($choices);

            $this->dto->setFormTypeOption('value_type_options.choices', array_keys($cases));
            $this->dto->setFormTypeOption(
                'value_type_options.choice_label',
                static fn ($value) => $cases[$value] instanceof TranslatableInterface ? $cases[$value] : $cases[$value]->name,
            );

            return $this;
        }

        $this->dto->setFormTypeOption('value_type_options.choices', $choices);

        return $this;
    }

    /**
     * @param array<string|TranslatableInterface|\UnitEnum> $choiceGenerator
     */
    public function setTranslatableChoices(array $choiceGenerator): self
    {
        // support passing a list of enum cases (e.g. MyEnum::cases()) ...
        if ($this->isEnumList($choiceGenerator)) {
            $choiceGenerator = $this->indexEnumCasesByValue($choiceGenerator);
        }

        $this->dto->setFormTypeOption('value_type_options.choices', array_keys($choiceGenerator));
        $this->dto->setFormTypeOption('value_type_options.choice_label', static fn ($value) => $choiceGenerator[$value]);

        return $this;
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

    /**
     * @param list<\UnitEnum> $cases
     *
     * @return array<string|int, \UnitEnum> enum cases indexed by their submitted value
     */
    private function indexEnumCasesByValue(array $cases): array
    {
        $indexed = [];
        foreach ($cases as $case) {
            $indexed[$case instanceof \BackedEnum ? $case->value : $case->name] = $case;
        }

        return $indexed;
    }

    /**
     * @param array<mixed> $values
     *
     * @phpstan-assert-if-true non-empty-list<\UnitEnum> $values
     */
    private function isEnumList(array $values): bool
    {
        if ([] === $values || !array_is_list($values)) {
            return false;
        }

        foreach ($values as $value) {
            if (!$value instanceof \UnitEnum) {
                return false;
            }
        }

        return true;
    }
}
