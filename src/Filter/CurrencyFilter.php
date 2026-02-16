<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CurrencyFilter implements FilterInterface
{
    use ChoiceFilterApplyTrait;
    use FilterTrait;

    public const OPTION_CURRENCY_CODES_TO_KEEP = 'currencyCodesToKeep';
    public const OPTION_CURRENCY_CODES_TO_REMOVE = 'currencyCodesToRemove';
    public const OPTION_PREFERRED_CHOICES = 'preferredChoices';

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceFilterType::class);
    }

    /**
     * @param string[] $currencyCodes
     */
    public function includeOnly(array $currencyCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_CURRENCY_CODES_TO_KEEP, $currencyCodes);

        return $this;
    }

    /**
     * @param string[] $currencyCodes
     */
    public function remove(array $currencyCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_CURRENCY_CODES_TO_REMOVE, $currencyCodes);

        return $this;
    }

    /**
     * @param string[] $currencyCodes
     */
    public function preferredChoices(array $currencyCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_PREFERRED_CHOICES, $currencyCodes);

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
}
