<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormPanelType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormRowType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminTabType;
use Symfony\Component\Uid\Ulid;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FormField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ICON = 'icon';
    public const OPTION_COLLAPSIBLE = 'collapsible';
    public const OPTION_COLLAPSED = 'collapsed';
    public const OPTION_ROW_BREAKPOINT = 'rowBreakPoint';

    /**
     * @internal Use the other named constructors instead (addPanel(), etc.)
     *
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null)
    {
        throw new \RuntimeException('Instead of this method, use the "addPanel()" method.');
    }

    /**
     * @param string|false|null $label
     */
    public static function addPanel($label = false, ?string $icon = null): self
    {
        $field = new self();

        return $field
            ->setFieldFqcn(__CLASS__)
            ->hideOnIndex()
            ->setProperty('ea_form_panel_'.(new Ulid()))
            ->setLabel($label)
            ->setFormType(EaFormPanelType::class)
            ->addCssClass('field-form_panel')
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->setCustomOption(self::OPTION_ICON, $icon)
            ->setCustomOption(self::OPTION_COLLAPSIBLE, false)
            ->setCustomOption(self::OPTION_COLLAPSED, false);
    }

    /**
     * @param string $breakpointName The name of the breakpoint where the new row is inserted
     *                               It must be a valid Bootstrap 5 name ('', 'sm', 'md', 'lg', 'xl', 'xxl')
     */
    public static function addRow(string $breakpointName = ''): self
    {
        $field = new self();

        $validBreakpointNames = ['', 'sm', 'md', 'lg', 'xl', 'xxl'];
        if (!\in_array($breakpointName, $validBreakpointNames, true)) {
            throw new \InvalidArgumentException(sprintf('The value passed to the "addRow()" method of "FormField" can only be one of these values: "%s" ("%s" was given).', implode(', ', $validBreakpointNames), $breakpointName));
        }

        return $field
            ->setFieldFqcn(__CLASS__)
            ->hideOnIndex()
            ->setProperty('ea_form_row_'.(new Ulid()))
            ->setFormType(EaFormRowType::class)
            ->addCssClass('field-form_row')
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->setCustomOption(self::OPTION_ROW_BREAKPOINT, $breakpointName);
    }

    /**
     * @return static
     */
    public static function addTab(string $label, ?string $icon = null): self
    {
        $field = new self();

        return $field
            ->setFieldFqcn(__CLASS__)
            ->hideOnIndex()
            ->hideOnDetail()
            ->setProperty('ea_form_tab_'.(new Ulid()))
            ->setLabel($label)
            ->setFormType(EasyAdminTabType::class)
            ->addCssClass('field-form_tab')
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->setCustomOption(self::OPTION_ICON, $icon);
    }

    public function setIcon(string $iconCssClass): self
    {
        $this->setCustomOption(self::OPTION_ICON, $iconCssClass);

        return $this;
    }

    public function collapsible(bool $collapsible = true): self
    {
        if (!$this->hasLabelOrIcon()) {
            throw new \InvalidArgumentException(sprintf('The %s() method used in one of your panels requires that the panel defines either a label or an icon, but it defines none of them.', __METHOD__));
        }

        $this->setCustomOption(self::OPTION_COLLAPSIBLE, $collapsible);

        return $this;
    }

    public function renderCollapsed(bool $collapsed = true): self
    {
        if (!$this->hasLabelOrIcon()) {
            throw new \InvalidArgumentException(sprintf('The %s() method used in one of your panels requires that the panel defines either a label or an icon, but it defines none of them.', __METHOD__));
        }

        $this->setCustomOption(self::OPTION_COLLAPSIBLE, true);
        $this->setCustomOption(self::OPTION_COLLAPSED, $collapsed);

        return $this;
    }

    private function hasLabelOrIcon(): bool
    {
        // don't use empty() because the label can contain only white spaces (it's a valid edge-case)
        return (null !== $this->dto->getLabel() && '' !== $this->dto->getLabel())
            || null !== $this->dto->getCustomOption(self::OPTION_ICON);
    }
}
