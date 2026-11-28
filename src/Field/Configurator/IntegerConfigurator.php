<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Intl\IntlFormatterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class IntegerConfigurator implements FieldConfiguratorInterface
{
    public function __construct(private IntlFormatterInterface $intlFormatter)
    {
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return IntegerField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (null === $value = $field->getValue()) {
            return;
        }

        $formatterAttributes = ['fraction_digit' => 0];

        $numberFormat = $field->getCustomOption(IntegerField::OPTION_NUMBER_FORMAT)
            ?? $context->getCrud()->getNumberFormat()
            ?? null;

        if (null !== $numberFormat) {
            $field->setFormattedValue(sprintf($numberFormat, $value));

            return;
        }

        $thousandsSeparator = $field->getCustomOption(IntegerField::OPTION_THOUSANDS_SEPARATOR)
            ?? $context->getCrud()->getThousandsSeparator()
            ?? null;
        if (null !== $thousandsSeparator) {
            $formatterAttributes['grouping_separator'] = $thousandsSeparator;
        }

        $field->setFormattedValue($this->intlFormatter->formatNumber($value, $formatterAttributes));
    }
}
