<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class LanguageFilter implements FilterInterface
{
    use ChoiceFilterApplyTrait;
    use FilterTrait;

    public const OPTION_LANGUAGE_CODES_TO_KEEP = 'languageCodesToKeep';
    public const OPTION_LANGUAGE_CODES_TO_REMOVE = 'languageCodesToRemove';
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
     * @param string[] $languageCodes
     */
    public function includeOnly(array $languageCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_LANGUAGE_CODES_TO_KEEP, $languageCodes);

        return $this;
    }

    /**
     * @param string[] $languageCodes
     */
    public function remove(array $languageCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_LANGUAGE_CODES_TO_REMOVE, $languageCodes);

        return $this;
    }

    /**
     * @param string[] $languageCodes
     */
    public function preferredChoices(array $languageCodes): self
    {
        $this->dto->setCustomOption(self::OPTION_PREFERRED_CHOICES, $languageCodes);

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
