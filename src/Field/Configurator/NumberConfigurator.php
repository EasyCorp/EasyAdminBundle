<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

final class NumberConfigurator implements FieldConfiguratorInterface
{
    private $intlFormatter;

    public function __construct(IntlFormatter $intlFormatter)
    {
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof NumberField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
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

        $formatterAttributes = [
            'fraction_digit' => $scale,
            'rounding_mode' => $this->getRoundingModeAsString($roundingMode),
        ];
        $field->setFormattedValue($this->intlFormatter->formatNumber($value, $formatterAttributes));
    }

    private function getRoundingModeAsString(int $mode): string
    {
        return [
            NumberToLocalizedStringTransformer::ROUND_DOWN => 'down',
            NumberToLocalizedStringTransformer::ROUND_FLOOR => 'floor',
            NumberToLocalizedStringTransformer::ROUND_UP => 'up',
            NumberToLocalizedStringTransformer::ROUND_CEILING => 'ceiling',
            NumberToLocalizedStringTransformer::ROUND_HALF_DOWN => 'halfdown',
            NumberToLocalizedStringTransformer::ROUND_HALF_EVEN => 'halfeven',
            NumberToLocalizedStringTransformer::ROUND_HALF_UP => 'halfup',
        ][$mode];
    }
}
