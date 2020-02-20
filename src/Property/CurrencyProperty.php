<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;

class CurrencyProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_SHOW_CODE = 'showCode';
    public const OPTION_SHOW_NAME = 'showName';
    public const OPTION_SHOW_SYMBOL = 'showSymbol';

    public function __construct()
    {
        $this
            ->setType('currency')
            ->setFormType(CurrencyType::class)
            ->setTemplateName('property/currency')
            ->setCustomOption(self::OPTION_SHOW_CODE, false)
            ->setCustomOption(self::OPTION_SHOW_NAME, true)
            ->setCustomOption(self::OPTION_SHOW_SYMBOL, true);
    }

    public function showCode(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_CODE, $isShown);

        return $this;
    }

    public function showName(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_NAME, $isShown);

        return $this;
    }

    public function showSymbol(bool $isShown = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_SYMBOL, $isShown);

        return $this;
    }
}
