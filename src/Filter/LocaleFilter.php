<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class LocaleFilter implements FilterInterface
{
    use ChoiceFilterApplyTrait;
    use FilterTrait;

    public const OPTION_LOCALE_CODES_TO_KEEP = 'localeCodesToKeep';
    public const OPTION_LOCALE_CODES_TO_REMOVE = 'localeCodesToRemove';
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
     * @param string[] $localeCodes
     */
    public function includeOnly(array $localeCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_LOCALE_CODES_TO_KEEP, $localeCodes);

        return $this;
    }

    /**
     * @param string[] $localeCodes
     */
    public function remove(array $localeCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_LOCALE_CODES_TO_REMOVE, $localeCodes);

        return $this;
    }

    /**
     * @param string[] $localeCodes
     */
    public function preferredChoices(array $localeCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_PREFERRED_CHOICES, $localeCodes);

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
