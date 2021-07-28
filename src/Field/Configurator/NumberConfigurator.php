<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NumberConfigurator implements FieldConfiguratorInterface
{
    private $intlFormatter;

    public function __construct(IntlFormatter $intlFormatter)
    {
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return NumberField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (null === $value = $field->getValue()) {
            return;
        }

        $scale = $field->getCustomOption(NumberField::OPTION_NUM_DECIMALS);
        $roundingMode = $field->getCustomOption(NumberField::OPTION_ROUNDING_MODE);
        $isStoredAsString = $field->getCustomOption(NumberField::OPTION_STORED_AS_STRING);

        $field->setFormTypeOptionIfNotSet('input', $isStoredAsString ? 'string' : 'number');
        $field->setFormTypeOptionIfNotSet('rounding_mode', $roundingMode);
        $field->setFormTypeOptionIfNotSet('scale', $scale);

        $formatterAttributes = ['rounding_mode' => $this->getRoundingModeAsString($roundingMode)];
        if (null !== $scale) {
            $formatterAttributes['fraction_digit'] = $scale;
        }

        $field->setFormattedValue($this->intlFormatter->formatNumber($value, $formatterAttributes));
    }

    private function getRoundingModeAsString(int $mode): string
    {
        return [
            \NumberFormatter::ROUND_DOWN => 'down',
            \NumberFormatter::ROUND_FLOOR => 'floor',
            \NumberFormatter::ROUND_UP => 'up',
            \NumberFormatter::ROUND_CEILING => 'ceiling',
            \NumberFormatter::ROUND_HALFDOWN => 'halfdown',
            \NumberFormatter::ROUND_HALFEVEN => 'halfeven',
            \NumberFormatter::ROUND_HALFUP => 'halfup',
        ][$mode];
    }
}
