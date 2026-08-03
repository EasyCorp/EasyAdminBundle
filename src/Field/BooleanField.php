<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class BooleanField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_RENDER_AS_SWITCH = 'renderAsSwitch';
    public const OPTION_SWITCH_VARIANT = 'switchVariant';
    public const OPTION_HIDE_VALUE_WHEN_TRUE = 'hideValueWhenTrue';
    public const OPTION_HIDE_VALUE_WHEN_FALSE = 'hideValueWhenFalse';
    public const OPTION_SWAP_LABEL_AND_VALUE = 'swapLabelAndValue';
    /** @internal */
    public const OPTION_TOGGLE_URL = 'toggleUrl';
    /** @internal */
    public const CSRF_TOKEN_NAME = 'ea-toggle';

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTextAlign(TextAlign::CENTER)
            ->setTemplateName('crud/field/boolean')
            ->setFormType(CheckboxType::class)
            ->addCssClass('field-boolean')
            ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-boolean.js')->onlyOnIndex())
            ->setCustomOption(self::OPTION_RENDER_AS_SWITCH, true)
            ->setCustomOption(self::OPTION_HIDE_VALUE_WHEN_TRUE, false)
            ->setCustomOption(self::OPTION_HIDE_VALUE_WHEN_FALSE, false)
            ->setCustomOption(self::OPTION_SWAP_LABEL_AND_VALUE, true);
    }

    public function renderAsSwitch(bool $isASwitch = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_SWITCH, $isASwitch);

        return $this;
    }

    /**
     * Renders the field as a switch whose "on" state uses the success (green) color.
     */
    public function renderAsSuccessSwitch(): self
    {
        $this->renderAsSwitch();
        $this->setCustomOption(self::OPTION_SWITCH_VARIANT, 'success');

        return $this;
    }

    /**
     * Renders the field as a switch whose "on" state uses the warning (amber) color.
     */
    public function renderAsWarningSwitch(): self
    {
        $this->renderAsSwitch();
        $this->setCustomOption(self::OPTION_SWITCH_VARIANT, 'warning');

        return $this;
    }

    /**
     * Renders the field as a switch whose "on" state uses the danger (red) color.
     */
    public function renderAsDangerSwitch(): self
    {
        $this->renderAsSwitch();
        $this->setCustomOption(self::OPTION_SWITCH_VARIANT, 'danger');

        return $this;
    }

    public function hideValueWhenTrue(bool $hide = true): self
    {
        $this->setCustomOption(self::OPTION_HIDE_VALUE_WHEN_TRUE, $hide);

        return $this;
    }

    public function hideValueWhenFalse(bool $hide = true): self
    {
        $this->setCustomOption(self::OPTION_HIDE_VALUE_WHEN_FALSE, $hide);

        return $this;
    }

    /**
     * On the detail page, boolean fields swap the usual label <-> value order
     * to display the value (which is always tiny) before the label.
     * Pass FALSE to this method to display the label and the value of boolean
     * fields in the same order as the rest of the fields.
     */
    public function swapLabelAndValue(bool $swap = true): self
    {
        $this->setCustomOption(self::OPTION_SWAP_LABEL_AND_VALUE, $swap);

        return $this;
    }
}
