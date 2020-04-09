<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NumberField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_NUM_DECIMALS = 'numDecimals';
    public const OPTION_ROUNDING_MODE = 'roundingMode';
    public const OPTION_STORED_AS_STRING = 'storedAsString';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/number')
            ->setFormType(NumberType::class)
            ->addCssClass('field-number')
            ->setCustomOption(self::OPTION_NUM_DECIMALS, null)
            ->setCustomOption(self::OPTION_ROUNDING_MODE, NumberToLocalizedStringTransformer::ROUND_HALF_UP)
            ->setCustomOption(self::OPTION_STORED_AS_STRING, false);
    }

    public function setNumDecimals(int $num): self
    {
        if ($num < 0) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be 0 or higher (%d given).', __METHOD__, $num));
        }

        $this->setCustomOption(self::OPTION_NUM_DECIMALS, $num);

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

    public function setStoredAsString(bool $asString = true): self
    {
        $this->setCustomOption(self::OPTION_STORED_AS_STRING, $asString);

        return $this;
    }
}
