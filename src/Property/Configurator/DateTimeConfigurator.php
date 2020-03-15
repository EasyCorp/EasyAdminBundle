<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Property\DateProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\DateTimeProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\TimeProperty;

final class DateTimeConfigurator implements PropertyConfiguratorInterface
{
    private $adminContextProvider;
    private $intlFormatter;

    public function __construct(AdminContextProvider $adminContextProvider, IntlFormatter $intlFormatter)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof DateTimeProperty
            || $propertyConfig instanceof DateProperty
            || $propertyConfig instanceof TimeProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        $crud = $this->adminContextProvider->getContext()->getCrud();
        $defaultDateFormat = $crud->getDateFormat();
        $defaultTimeFormat = $crud->getTimeFormat();
        $defaultDateTimePattern = $crud->getDateTimePattern();
        $defaultTimezone = $crud->getTimezone();

        $dateFormat = $propertyConfig->getCustomOption(DateTimeProperty::OPTION_DATE_FORMAT) ?? $defaultDateFormat;
        $timeFormat = $propertyConfig->getCustomOption(DateTimeProperty::OPTION_TIME_FORMAT) ?? $defaultTimeFormat;
        $dateTimePattern = $propertyConfig->getCustomOption(DateTimeProperty::OPTION_DATETIME_PATTERN) ?? $defaultDateTimePattern;
        $timezone = $propertyConfig->getCustomOption(DateTimeProperty::OPTION_TIMEZONE) ?? $defaultTimezone;

        if ($propertyConfig instanceof DateTimeProperty) {
            $formattedValue = $this->intlFormatter->formatDateTime($propertyConfig->getValue(), $dateFormat, $timeFormat, $dateTimePattern, $timezone);
        } elseif ($propertyConfig instanceof DateProperty) {
            $formattedValue = $this->intlFormatter->formatDate($propertyConfig->getValue(), $dateFormat, $dateTimePattern, $timezone);
        } elseif ($propertyConfig instanceof TimeProperty) {
            $formattedValue = $this->intlFormatter->formatTime($propertyConfig->getValue(), $timeFormat, $dateTimePattern, $timezone);
        }

        $propertyConfig->setFormattedValue($formattedValue);
    }
}
