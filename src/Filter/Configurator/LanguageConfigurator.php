<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\LanguageFilter;
use Symfony\Component\Intl\Languages;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class LanguageConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return LanguageFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $useAlpha3Codes = true === $filterDto->getCustomOption(LanguageFilter::OPTION_USE_ALPHA3_CODES);

        $languages = $useAlpha3Codes
            ? Languages::getAlpha3Names()
            : Languages::getNames();

        if (null !== $codesToKeep = $filterDto->getCustomOption(LanguageFilter::OPTION_LANGUAGE_CODES_TO_KEEP)) {
            $languages = array_intersect_key($languages, array_flip($codesToKeep));
        }

        if (null !== $codesToRemove = $filterDto->getCustomOption(LanguageFilter::OPTION_LANGUAGE_CODES_TO_REMOVE)) {
            $languages = array_diff_key($languages, array_flip($codesToRemove));
        }

        $choices = array_flip($languages);

        $filterDto->setFormTypeOption('value_type_options.choices', $choices);
        $filterDto->setFormTypeOption('value_type_options.choice_translation_domain', false);

        if (null !== $preferredChoices = $filterDto->getCustomOption(LanguageFilter::OPTION_PREFERRED_CHOICES)) {
            $filterDto->setFormTypeOption('value_type_options.preferred_choices', $preferredChoices);
        }
    }
}
