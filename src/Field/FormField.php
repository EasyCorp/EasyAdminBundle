<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormPanelType;
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

    /**
     * @internal Use the other named constructors instead (addPanel(), etc.)
     */
    public static function new(string $propertyName, ?string $label = null)
    {
        throw new \RuntimeException('Instead of this method, use the "addPanel()" method.');
    }

    public static function addPanel(?string $label = null, ?string $icon = null): self
    {
        $field = new self();

        return $field
            ->setFieldFqcn(__CLASS__)
            ->hideOnIndex()
            ->setProperty('ea_form_panel_'.(new Ulid()))
            ->setLabel($label)
            ->setTemplateName('crud/field/form_panel')
            ->setFormType(EaFormPanelType::class)
            ->addCssClass('field-form_panel')
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->setCustomOption(self::OPTION_ICON, $icon)
            ->setCustomOption(self::OPTION_COLLAPSIBLE, false)
            ->setCustomOption(self::OPTION_COLLAPSED, false);
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
