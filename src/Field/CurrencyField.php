<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;

class CurrencyField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_SHOW_CODE = 'showCode';
    public const OPTION_SHOW_NAME = 'showName';
    public const OPTION_SHOW_SYMBOL = 'showSymbol';

    public function __construct()
    {
        $this
            ->setType('currency')
            ->setFormType(CurrencyType::class)
            ->setTemplateName('crud/field/currency')
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
