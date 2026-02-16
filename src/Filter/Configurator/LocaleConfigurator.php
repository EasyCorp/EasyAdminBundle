<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\LocaleFilter;
use Symfony\Component\Intl\Locales;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class LocaleConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return LocaleFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $locales = Locales::getNames();

        if (null !== $codesToKeep = $filterDto->getCustomOption(LocaleFilter::OPTION_LOCALE_CODES_TO_KEEP)) {
            $locales = array_intersect_key($locales, array_flip($codesToKeep));
        }

        if (null !== $codesToRemove = $filterDto->getCustomOption(LocaleFilter::OPTION_LOCALE_CODES_TO_REMOVE)) {
            $locales = array_diff_key($locales, array_flip($codesToRemove));
        }

        $choices = array_flip($locales);

        $filterDto->setFormTypeOption('value_type_options.choices', $choices);
        $filterDto->setFormTypeOption('value_type_options.choice_translation_domain', false);

        if (null !== $preferredChoices = $filterDto->getCustomOption(LocaleFilter::OPTION_PREFERRED_CHOICES)) {
            $filterDto->setFormTypeOption('value_type_options.preferred_choices', $preferredChoices);
        }
    }
}
