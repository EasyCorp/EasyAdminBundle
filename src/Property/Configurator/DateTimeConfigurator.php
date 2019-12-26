<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Formatter\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Property\DateProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\DateTimeProperty;
use EasyCorp\Bundle\EasyAdminBundle\Property\TimeProperty;

final class DateTimeConfigurator implements PropertyConfiguratorInterface
{
    private $intlFormatter;

    public function __construct(IntlFormatter $intlFormatter)
    {
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
        if ($propertyConfig instanceof DateTimeProperty) {
            $formattedValue = $this->intlFormatter->formatDateTime(
                $propertyConfig->getValue(),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_DATE_FORMAT),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_TIME_FORMAT),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_DATETIME_PATTERN),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_TIMEZONE)
            );
        } elseif ($propertyConfig instanceof DateProperty) {
            $formattedValue = $this->intlFormatter->formatDate(
                $propertyConfig->getValue(),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_DATE_FORMAT),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_DATETIME_PATTERN),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_TIMEZONE)
            );
        } elseif ($propertyConfig instanceof TimeProperty) {
            $formattedValue = $this->intlFormatter->formatTime(
                $propertyConfig->getValue(),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_TIME_FORMAT),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_DATETIME_PATTERN),
                $propertyConfig->getCustomOption(DateTimeProperty::OPTION_TIMEZONE)
            );
        }

        $propertyConfig->setFormattedValue($formattedValue);
    }
}
