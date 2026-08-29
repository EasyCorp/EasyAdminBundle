<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Intl\IntlFormatterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class NumberConfigurator implements FieldConfiguratorInterface
{
    public function __construct(private IntlFormatterInterface $intlFormatter)
    {
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return NumberField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $scale = $field->getCustomOption(NumberField::OPTION_NUM_DECIMALS);
        $roundingMode = $field->getCustomOption(NumberField::OPTION_ROUNDING_MODE);
        $isStoredAsString = true === $field->getCustomOption(NumberField::OPTION_STORED_AS_STRING);
        $isStoredAsBcMathNumber = true === $field->getCustomOption(NumberField::OPTION_STORED_AS_BCMATH_NUMBER);

        if ($isStoredAsString && $isStoredAsBcMathNumber) {
            throw new \InvalidArgumentException(sprintf('The "%s" field cannot use both "setStoredAsString()" and "setStoredAsBcMathNumber()" options at the same time.', $field->getProperty()));
        }

        if ($isStoredAsBcMathNumber && \PHP_VERSION_ID < 80400) {
            throw new \LogicException('The "setStoredAsBcMathNumber()" option requires PHP 8.4 or higher.');
        }

        $input = match (true) {
            $isStoredAsString, $isStoredAsBcMathNumber => 'string',
            default => 'number',
        };
        $field->setFormTypeOptionIfNotSet('input', $input);
        $field->setFormTypeOptionIfNotSet('rounding_mode', $roundingMode);
        $field->setFormTypeOptionIfNotSet('scale', $scale);

        if ($isStoredAsBcMathNumber) {
            $field->setFormTypeOption('ea_bcmath_number', true);
        }

        if (null === $value = $field->getValue()) {
            return;
        }

        if ($isStoredAsBcMathNumber) {
            $value = (float) (string) $value;
        }

        $formatterAttributes = ['rounding_mode' => $this->getRoundingModeAsString($roundingMode)];
        if (null !== $scale) {
            $formatterAttributes['fraction_digit'] = $scale;
        }

        $numberFormat = $field->getCustomOption(NumberField::OPTION_NUMBER_FORMAT)
            ?? $context->getCrud()->getNumberFormat()
            ?? null;

        if (null !== $numberFormat) {
            $field->setFormattedValue(sprintf($numberFormat, $value));

            return;
        }

        $thousandsSeparator = $field->getCustomOption(NumberField::OPTION_THOUSANDS_SEPARATOR)
            ?? $context->getCrud()->getThousandsSeparator()
            ?? null;
        if (null !== $thousandsSeparator) {
            $formatterAttributes['grouping_separator'] = $thousandsSeparator;
        }

        $decimalSeparator = $field->getCustomOption(NumberField::OPTION_DECIMAL_SEPARATOR)
            ?? $context->getCrud()->getDecimalSeparator()
            ?? null;
        if (null !== $decimalSeparator) {
            $formatterAttributes['decimal_separator'] = $decimalSeparator;
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
