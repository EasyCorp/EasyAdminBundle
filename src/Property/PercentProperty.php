<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Intl\Currencies;

class PercentProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_NUM_DECIMALS = 'numDecimals';
    public const OPTION_STORED_AS_FRACTIONAL = 'storedAsFractional';
    public const OPTION_SYMBOL = 'symbol';

    public function __construct()
    {
        $this
            ->setType('percent')
            ->setFormType(PercentType::class)
            ->setTemplateName('property/percent')
            ->setTextAlign('right')
            ->setCustomOption(self::OPTION_NUM_DECIMALS, 0)
            ->setCustomOption(self::OPTION_STORED_AS_FRACTIONAL, true)
            ->setCustomOption(self::OPTION_SYMBOL, '%');
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
     * If your percentages can have decimals (e.g. 15.6%) set this option to TRUE
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
}
