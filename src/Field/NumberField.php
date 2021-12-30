<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
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

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/number')
            ->setFormType(NumberType::class)
            ->addCssClass('field-number')
            ->setDefaultColumns('col-md-4 col-xxl-3')
            ->setCustomOption(self::OPTION_NUM_DECIMALS, null)
            ->setCustomOption(self::OPTION_ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP)
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
            'ROUND_DOWN' => \NumberFormatter::ROUND_DOWN,
            'ROUND_FLOOR' => \NumberFormatter::ROUND_FLOOR,
            'ROUND_UP' => \NumberFormatter::ROUND_UP,
            'ROUND_CEILING' => \NumberFormatter::ROUND_CEILING,
            'ROUND_HALF_DOWN' => \NumberFormatter::ROUND_HALFDOWN,
            'ROUND_HALF_EVEN' => \NumberFormatter::ROUND_HALFEVEN,
            'ROUND_HALF_UP' => \NumberFormatter::ROUND_HALFUP,
        ];

        if (!\in_array($mode, $validModes, true)) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s()" method must be the value of any of the following constants from the %s class: %s.', __METHOD__, \NumberFormatter::class, implode(', ', array_keys($validModes))));
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
