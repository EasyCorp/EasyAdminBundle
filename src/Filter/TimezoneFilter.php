<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TimezoneFilter implements FilterInterface
{
    use ChoiceFilterApplyTrait;
    use FilterTrait;

    public const OPTION_TIMEZONE_IDENTIFIERS_TO_KEEP = 'timezoneIdentifiersToKeep';
    public const OPTION_TIMEZONE_IDENTIFIERS_TO_REMOVE = 'timezoneIdentifiersToRemove';
    public const OPTION_PREFERRED_CHOICES = 'preferredChoices';
    public const OPTION_FOR_COUNTRY_CODE = 'forCountryCode';

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
     * @param string[] $timezoneIdentifiers
     */
    public function includeOnly(array $timezoneIdentifiers): self
    {
        $this->dto->setCustomOption(self::OPTION_TIMEZONE_IDENTIFIERS_TO_KEEP, $timezoneIdentifiers);

        return $this;
    }

    /**
     * @param string[] $timezoneIdentifiers
     */
    public function remove(array $timezoneIdentifiers): self
    {
        $this->dto->setCustomOption(self::OPTION_TIMEZONE_IDENTIFIERS_TO_REMOVE, $timezoneIdentifiers);

        return $this;
    }

    /**
     * @param string[] $timezoneIdentifiers
     */
    public function preferredChoices(array $timezoneIdentifiers): self
    {
        $this->dto->setCustomOption(self::OPTION_PREFERRED_CHOICES, $timezoneIdentifiers);

        return $this;
    }

    public function forCountryCode(string $countryCode): self
    {
        $this->dto->setCustomOption(self::OPTION_FOR_COUNTRY_CODE, $countryCode);

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
