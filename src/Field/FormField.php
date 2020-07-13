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
            ->setCustomOption(self::OPTION_ICON, $icon);
    }

    public function setIcon(string $iconCssClass): self
    {
        $this->setCustomOption(self::OPTION_ICON, $iconCssClass);

        return $this;
    }
}
