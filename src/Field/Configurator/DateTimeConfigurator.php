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

        $defaultTimezone = $crud->getTimezone();
        $timezone = $field->getCustomOption(DateTimeField::OPTION_TIMEZONE) ?? $defaultTimezone;

        $formattedValue = $field->getValue();
        if (DateTimeField::class === $field->getFieldFqcn()) {
            $defaultDateTimePattern = $crud->getDateTimePattern();
            $dateTimePattern = $field->getCustomOption(DateTimeField::OPTION_DATETIME_PATTERN) ?? $defaultDateTimePattern;
            $formattedValue = $this->intlFormatter->formatDateTime($field->getValue(), null, null, $dateTimePattern, $timezone);
        } elseif (DateField::class === $field->getFieldFqcn()) {
            $defaultDatePattern = $crud->getDatePattern();
            $datePattern = $field->getCustomOption(DateField::OPTION_DATE_PATTERN) ?? $defaultDatePattern;
            $formattedValue = $this->intlFormatter->formatDate($field->getValue(), null, $datePattern, $timezone);
        } elseif (TimeField::class === $field->getFieldFqcn()) {
            $defaultTimePattern = $crud->getTimePattern();
            $timePattern = $field->getCustomOption(TimeField::OPTION_TIME_PATTERN) ?? $defaultTimePattern;
            $formattedValue = $this->intlFormatter->formatTime($field->getValue(), null, $timePattern, $timezone);
        }

        $widgetOption = $field->getCustomOption(DateTimeField::OPTION_WIDGET);
        if (DateTimeField::WIDGET_NATIVE === $widgetOption) {
            $field->setFormTypeOption('widget', 'single_text');
            $field->setFormTypeOption('html5', true);
        } elseif(DateTimeField::WIDGET_CHOICE === $widgetOption) {
            $field->setFormTypeOption('widget', 'choice');
            $field->setFormTypeOption('html5', true);
        } elseif(DateTimeField::WIDGET_TEXT === $widgetOption) {
            $field->setFormTypeOption('widget', 'single_text');
            $field->setFormTypeOption('html5', false);
        }

        $field->setFormattedValue($formattedValue);

        $doctrineDataType = $entityDto->getPropertyMetadata($field->getProperty())->get('type');
        $isImmutableDateTime = \in_array($doctrineDataType, [Types::DATETIME_IMMUTABLE, Types::DATE_IMMUTABLE, Types::TIME_IMMUTABLE], true);
        if ($isImmutableDateTime) {
            $field->setFormTypeOptionIfNotSet('input', 'datetime_immutable');
        }
    }
}
