<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CountryFilter implements FilterInterface
{
    use ChoiceFilterApplyTrait;
    use FilterTrait;

    public const OPTION_COUNTRY_CODES_TO_KEEP = 'countryCodesToKeep';
    public const OPTION_COUNTRY_CODES_TO_REMOVE = 'countryCodesToRemove';
    public const OPTION_PREFERRED_CHOICES = 'preferredChoices';
    public const OPTION_USE_ALPHA3_CODES = 'useAlpha3Codes';

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
     * @param string[] $countryCodes
     */
    public function includeOnly(array $countryCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_COUNTRY_CODES_TO_KEEP, $countryCodes);

        return $this;
    }

    /**
     * @param string[] $countryCodes
     */
    public function remove(array $countryCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_COUNTRY_CODES_TO_REMOVE, $countryCodes);

        return $this;
    }

    /**
     * @param string[] $countryCodes
     */
    public function preferredChoices(array $countryCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_PREFERRED_CHOICES, $countryCodes);

        return $this;
    }

    public function useAlpha3Codes(bool $useAlpha3Codes = true): self
    {
        $this->dto->setCustomOption(self::OPTION_USE_ALPHA3_CODES, $useAlpha3Codes);

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
