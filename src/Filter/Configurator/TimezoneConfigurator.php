<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TimezoneFilter;
use Symfony\Component\Intl\Timezones;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class TimezoneConfigurator implements FilterConfiguratorInterface
{
    public function supports(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): bool
    {
        return TimezoneFilter::class === $filterDto->getFqcn();
    }

    public function configure(FilterDto $filterDto, ?FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        $countryCode = $filterDto->getCustomOption(TimezoneFilter::OPTION_FOR_COUNTRY_CODE);
        $timezones = null === $countryCode
            ? Timezones::getNames()
            : array_combine(
                $ids = Timezones::forCountryCode($countryCode),
                array_map(static fn (string $id): string => Timezones::getName($id), $ids)
            );

        if (null !== $identifiersToKeep = $filterDto->getCustomOption(TimezoneFilter::OPTION_TIMEZONE_IDENTIFIERS_TO_KEEP)) {
            $timezones = array_intersect_key($timezones, array_flip($identifiersToKeep));
        }

        if (null !== $identifiersToRemove = $filterDto->getCustomOption(TimezoneFilter::OPTION_TIMEZONE_IDENTIFIERS_TO_REMOVE)) {
            $timezones = array_diff_key($timezones, array_flip($identifiersToRemove));
        }

        $choices = array_flip($timezones);

        $filterDto->setFormTypeOption('value_type_options.choices', $choices);
        $filterDto->setFormTypeOption('value_type_options.choice_translation_domain', false);

        if (null !== $preferredChoices = $filterDto->getCustomOption(TimezoneFilter::OPTION_PREFERRED_CHOICES)) {
            $filterDto->setFormTypeOption('value_type_options.preferred_choices', $preferredChoices);
        }
    }
}
