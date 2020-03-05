<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Formatter\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Property\NumberProperty;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

final class NumberConfigurator implements PropertyConfiguratorInterface
{
    private $intlFormatter;

    public function __construct(IntlFormatter $intlFormatter)
    {
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof NumberProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if (null === $value = $propertyConfig->getValue()) {
            return;
        }

        $scale = $propertyConfig->getCustomOption(NumberProperty::OPTION_NUM_DECIMALS);
        $roundingMode = $propertyConfig->getCustomOption(NumberProperty::OPTION_ROUNDING_MODE);
        $isStoredAsString = $propertyConfig->getCustomOption(NumberProperty::OPTION_STORED_AS_STRING);

        $propertyConfig->setFormTypeOptionIfNotSet('input', $isStoredAsString ? 'string' : 'number');
        $propertyConfig->setFormTypeOptionIfNotSet('rounding_mode', $roundingMode);
        $propertyConfig->setFormTypeOptionIfNotSet('scale', $scale);

        $formatterAttributes = [
            'fraction_digit' => $scale,
            'rounding_mode' => $this->getRoundingModeAsString($roundingMode),
        ];
        $propertyConfig->setFormattedValue($this->intlFormatter->formatNumber($value, $formatterAttributes));
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
