<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\CurrencyFilter;
use Symfony\Component\Intl\Currencies;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CurrencyConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return CurrencyFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $currencies = Currencies::getNames();

        if (null !== $codesToKeep = $filterDto->getCustomOption(CurrencyFilter::OPTION_CURRENCY_CODES_TO_KEEP)) {
            $currencies = array_intersect_key($currencies, array_flip($codesToKeep));
        }

        if (null !== $codesToRemove = $filterDto->getCustomOption(CurrencyFilter::OPTION_CURRENCY_CODES_TO_REMOVE)) {
            $currencies = array_diff_key($currencies, array_flip($codesToRemove));
        }

        $choices = array_flip($currencies);

        $filterDto->setFormTypeOption('value_type_options.choices', $choices);
        $filterDto->setFormTypeOption('value_type_options.choice_translation_domain', false);

        if (null !== $preferredChoices = $filterDto->getCustomOption(CurrencyFilter::OPTION_PREFERRED_CHOICES)) {
            $filterDto->setFormTypeOption('value_type_options.preferred_choices', $preferredChoices);
        }
    }
}
