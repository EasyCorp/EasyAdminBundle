<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\PercentType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class PercentField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_NUM_DECIMALS = 'numDecimals';
    public const OPTION_STORED_AS_FRACTIONAL = 'storedAsFractional';
    public const OPTION_SYMBOL = 'symbol';
    public const OPTION_ROUNDING_MODE = 'roundingMode';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/percent')
            ->setFormType(PercentType::class)
            ->addCssClass('field-percent')
            ->setTextAlign(TextAlign::RIGHT)
            ->setCustomOption(self::OPTION_NUM_DECIMALS, 0)
            ->setCustomOption(self::OPTION_STORED_AS_FRACTIONAL, true)
            ->setCustomOption(self::OPTION_SYMBOL, '%')
            ->setCustomOption(self::OPTION_ROUNDING_MODE, NumberToLocalizedStringTransformer::ROUND_HALF_UP)
        ;
    }

    public function setNumDecimals(int $num): self
    {
        if ($num < 0) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 0 or higher (%d given).', __METHOD__, $num));
        }

        $this->setCustomOption(self::OPTION_NUM_DECIMALS, $num);

        return $this;
    }

    /**
     * If true, 15% is stored as 0.15; If false, 15% is stored as 15
     * If your percentages can have decimals (e.g. 15.6%) set this option to TRUE.
     */
    public function setStoredAsFractional(bool $isFractional = true): self
    {
        $this->setCustomOption(self::OPTION_STORED_AS_FRACTIONAL, $isFractional);

        return $this;
    }

    /**
     * @param string|false $symbolOrFalse
     */
    public function setSymbol($symbolOrFalse): self
    {
        $this->setCustomOption(self::OPTION_SYMBOL, $symbolOrFalse);

        return $this;
    }

    public function setRoundingMode(int $mode): self
    {
        $validModes = [
            'ROUND_DOWN' => NumberToLocalizedStringTransformer::ROUND_DOWN,
            'ROUND_FLOOR' => NumberToLocalizedStringTransformer::ROUND_FLOOR,
            'ROUND_UP' => NumberToLocalizedStringTransformer::ROUND_UP,
            'ROUND_CEILING' => NumberToLocalizedStringTransformer::ROUND_CEILING,
            'ROUND_HALF_DOWN' => NumberToLocalizedStringTransformer::ROUND_HALF_DOWN,
            'ROUND_HALF_EVEN' => NumberToLocalizedStringTransformer::ROUND_HALF_EVEN,
            'ROUND_HALF_UP' => NumberToLocalizedStringTransformer::ROUND_HALF_UP,
        ];

        if (!\in_array($mode, $validModes, true)) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be the value of any of the following constants from the %s class: %s.', __METHOD__, NumberToLocalizedStringTransformer::class, implode(', ', array_keys($validModes))));
        }

        $this->setCustomOption(self::OPTION_ROUNDING_MODE, $mode);

        return $this;
    }
}
