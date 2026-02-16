<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\CountryFilter;
use Symfony\Component\Intl\Countries;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CountryConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return CountryFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $useAlpha3Codes = true === $filterDto->getCustomOption(CountryFilter::OPTION_USE_ALPHA3_CODES);

        $countries = $useAlpha3Codes
            ? Countries::getAlpha3Names()
            : Countries::getNames();

        if (null !== $codesToKeep = $filterDto->getCustomOption(CountryFilter::OPTION_COUNTRY_CODES_TO_KEEP)) {
            $countries = array_intersect_key($countries, array_flip($codesToKeep));
        }

        if (null !== $codesToRemove = $filterDto->getCustomOption(CountryFilter::OPTION_COUNTRY_CODES_TO_REMOVE)) {
            $countries = array_diff_key($countries, array_flip($codesToRemove));
        }

        $choices = array_flip($countries);

        $filterDto->setFormTypeOption('value_type_options.choices', $choices);
        $filterDto->setFormTypeOption('value_type_options.choice_translation_domain', false);

        if (null !== $preferredChoices = $filterDto->getCustomOption(CountryFilter::OPTION_PREFERRED_CHOICES)) {
            $filterDto->setFormTypeOption('value_type_options.preferred_choices', $preferredChoices);
        }
    }
}
