<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;

final class DateTimeConfigurator implements FieldConfiguratorInterface
{
    private $adminContextProvider;
    private $intlFormatter;

    public function __construct(AdminContextProvider $adminContextProvider, IntlFormatter $intlFormatter)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof DateTimeField
            || $field instanceof DateField
            || $field instanceof TimeField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        $crud = $this->adminContextProvider->getContext()->getCrud();
        $defaultDateFormat = $crud->getDateFormat();
        $defaultTimeFormat = $crud->getTimeFormat();
        $defaultDateTimePattern = $crud->getDateTimePattern();
        $defaultTimezone = $crud->getTimezone();

        $dateFormat = $field->getCustomOption(DateTimeField::OPTION_DATE_FORMAT) ?? $defaultDateFormat;
        $timeFormat = $field->getCustomOption(DateTimeField::OPTION_TIME_FORMAT) ?? $defaultTimeFormat;
        $dateTimePattern = $field->getCustomOption(DateTimeField::OPTION_DATETIME_PATTERN) ?? $defaultDateTimePattern;
        $timezone = $field->getCustomOption(DateTimeField::OPTION_TIMEZONE) ?? $defaultTimezone;

        if ($field instanceof DateTimeField) {
            $formattedValue = $this->intlFormatter->formatDateTime($field->getValue(), $dateFormat, $timeFormat, $dateTimePattern, $timezone);
        } elseif ($field instanceof DateField) {
            $formattedValue = $this->intlFormatter->formatDate($field->getValue(), $dateFormat, $dateTimePattern, $timezone);
        } elseif ($field instanceof TimeField) {
            $formattedValue = $this->intlFormatter->formatTime($field->getValue(), $timeFormat, $dateTimePattern, $timezone);
        }

        $field->setFormattedValue($formattedValue);
    }
}
