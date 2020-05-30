<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DateTimeConfigurator implements FieldConfiguratorInterface
{
    private $intlFormatter;

    public function __construct(IntlFormatter $intlFormatter)
    {
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return \in_array($field->getFieldFqcn(), [DateTimeField::class, DateField::class, TimeField::class], true);
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $crud = $context->getCrud();
        $defaultDateFormat = $crud->getDateFormat();
        $defaultTimeFormat = $crud->getTimeFormat();
        $defaultDateTimePattern = $crud->getDateTimePattern();
        $defaultTimezone = $crud->getTimezone();

        $dateFormat = $field->getCustomOption(DateTimeField::OPTION_DATE_FORMAT) ?? $defaultDateFormat;
        $timeFormat = $field->getCustomOption(DateTimeField::OPTION_TIME_FORMAT) ?? $defaultTimeFormat;
        $dateTimePattern = $field->getCustomOption(DateTimeField::OPTION_DATETIME_PATTERN) ?? $defaultDateTimePattern;
        $timezone = $field->getCustomOption(DateTimeField::OPTION_TIMEZONE) ?? $defaultTimezone;

        $formattedValue = $field->getValue();
        if (DateTimeField::class === $field->getFieldFqcn()) {
            $formattedValue = $this->intlFormatter->formatDateTime($field->getValue(), $dateFormat, $timeFormat, $dateTimePattern, $timezone);
        } elseif (DateField::class === $field->getFieldFqcn()) {
            $formattedValue = $this->intlFormatter->formatDate($field->getValue(), $dateFormat, $dateTimePattern, $timezone);
        } elseif (TimeField::class === $field->getFieldFqcn()) {
            $formattedValue = $this->intlFormatter->formatTime($field->getValue(), $timeFormat, $dateTimePattern, $timezone);
        }

        $field->setFormattedValue($formattedValue);

        $doctrineDataType = $entityDto->getPropertyMetadata($field->getProperty())->get('type');
        $isImmutableDateTime = \in_array($doctrineDataType, [Types::DATETIME_IMMUTABLE, Types::DATE_IMMUTABLE, Types::TIME_IMMUTABLE], true);
        if ($isImmutableDateTime) {
            $field->setFormTypeOptionIfNotSet('input', 'datetime_immutable');
        }
    }
}
