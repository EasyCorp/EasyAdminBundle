<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CurrencyField implements FieldInterface
{
    use FieldTrait;

    // the ISO 4217 standard uses 'XXX' for transactions where no currency is involved: https://en.wikipedia.org/wiki/ISO_4217#Active_codes_(list_one)
    public const CURRENCY_NONE = 'XXX';

    public const OPTION_SHOW_CODE = 'showCode';
    public const OPTION_SHOW_NAME = 'showName';
    public const OPTION_SHOW_SYMBOL = 'showSymbol';
    public const OPTION_BADGE_VARIANT = 'badgeVariant';

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/currency')
            ->setFormType(CurrencyType::class)
            ->addCssClass('field-currency')
            ->setDefaultColumns('col-md-4 col-xxl-3')
            ->setCustomOption(self::OPTION_SHOW_CODE, false)
            ->setCustomOption(self::OPTION_SHOW_NAME, true)
            ->setCustomOption(self::OPTION_SHOW_SYMBOL, true)
            ->setCustomOption(self::OPTION_BADGE_VARIANT, null);
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

    /**
     * Sets the visual style of the badges that display the currency code and symbol.
     * Use one of these values: 'light', 'solid', 'outline' or 'dot'. By default,
     * it uses the badge variant configured with Crud::setBadgeVariant() ('light' if not set).
     */
    public function setBadgeVariant(string|BadgeVariant $variant): self
    {
        $this->setCustomOption(self::OPTION_BADGE_VARIANT, BadgeVariant::fromStyle($variant)->value);

        return $this;
    }
}
