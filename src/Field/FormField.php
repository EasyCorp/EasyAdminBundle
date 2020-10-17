<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormPanelType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormTabType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormGroupType;
use Symfony\Component\Uid\Ulid;

/**
 * This class is used to add some special decorator fields needed to create complex form layouts.
 * There are 3 of these special fields, corresponding to 3 hierarchy levels:
 * 1st the PANEL ; 2nd the TAB ; 3rd the GROUP
 *
 * The hierarchy of fields is organized like follows:
 * A field necessarily has a GROUP parent.
 * A GROUP necessarily has a TAB   parent.
 * A TAB   necessarily has a PANEL parent.
 * So a form is composed at least of one panel, one tab, one group and one field.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FormField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ICON = 'icon';
    public const OPTION_COLLAPSIBLE = 'collapsible';
    public const OPTION_COLLAPSED = 'collapsed';

    private const FORM_TYPES = [
        'panel' => EaFormPanelType::class,
        'tab'   => EaFormTabType::class,
        'group' => EaFormGroupType::class,
    ];

    /**
     * @internal Use the other named constructors instead (addPanel(), addTab() or addGroup())
     */
    public static function new(string $propertyName, ?string $label = null)
    {
        throw new \RuntimeException('Instead of this method, use the "addPanel()", "addTab()" or "addGroup()" methods.');
    }

    private static function add(string $type, ?string $label = null, ?string $icon = null)
    {
        if (! $formType = self::FORM_TYPES[$type] ?? null) {
            return null;
        }

        $field = new self();

        return $field
            ->setFieldFqcn(__CLASS__)
            ->isDecorator(true)
            ->setProperty("ea_form_{$type}_".(new Ulid()))
            ->setLabel($label)
            ->setTemplateName("crud/field/form_$type")
            ->setFormType($formType)
            ->addCssClass("field-form_$type")
            ->setCustomOption(self::OPTION_ICON, $icon);
    }

    public static function addPanel(?string $label = null, ?string $icon = null): self
    {
        return self::add('panel', $label, $icon)
            ->setCustomOption(self::OPTION_COLLAPSIBLE, false)
            ->setCustomOption(self::OPTION_COLLAPSED, false);
    }

    public static function addTab(?string $label = null, ?string $icon = null): self
    {
        return self::add('tab', $label, $icon);
    }

    public static function addGroup(?string $label = null, ?string $icon = null): self
    {
        return self::add('group', $label, $icon);
    }

    public function setIcon(string $iconCssClass): self
    {
        $this->setCustomOption(self::OPTION_ICON, $iconCssClass);

        return $this;
    }

    public function collapsible(bool $collapsible = true): self
    {
        if (EaFormPanelType::class !== $this->dto->getFormType()) {
            throw new \InvalidArgumentException(sprintf('The %s() method is only to used on panel, not on tab neither group.', __METHOD__));
        }

        if (!$this->hasLabelOrIcon()) {
            throw new \InvalidArgumentException(sprintf('The %s() method used in one of your panels requires that the panel defines either a label or an icon, but it defines none of them.', __METHOD__));
        }

        $this->setCustomOption(self::OPTION_COLLAPSIBLE, $collapsible);

        return $this;
    }

    public function renderCollapsed(bool $collapsed = true): self
    {
        if (EaFormPanelType::class !== $this->dto->getFormType()) {
            throw new \InvalidArgumentException(sprintf('The %s() method is only to used on panel, not on tab neither group.', __METHOD__));
        }

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
