<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateIntervalField;

/**
 * @author Pascal CESCON <pascal.cescon@gmail.com>
 */
final class DateIntervalConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return DateIntervalField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $value = $field->getValue();

        if (!$value instanceof \DateInterval) {
            return;
        }

        $format = $field->getCustomOption(DateIntervalField::OPTION_FORMAT)
            ?? $context->getCrud()?->getDateIntervalFormat();

        if (null !== $format) {
            $field->setFormattedValue($value->format($format));

            return;
        }

        // sentinel: keep formattedValue non-null so the CommonPostConfigurator does not
        // swap our template for label/null; the template builds the localized string
        // from $field->getValue() (the original DateInterval).
        $field->setFormattedValue('');
    }
}