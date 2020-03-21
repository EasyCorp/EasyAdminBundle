<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormPanelType;

final class FormPanelField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ICON = 'icon';

    public static function new(?string $label = null)
    {
        $field = new self();
        $field->property = '__unknown__';
        $field->label = $label ?? false;

        return $field;
    }

    public function __construct()
    {
        $this
            ->setType('form_panel')
            ->setFormType(EaFormPanelType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->setTemplateName('crud/field/form_panel')
            ->setCustomOption(self::OPTION_ICON, null);
    }

    public function setIcon(string $iconCssClass): self
    {
        $this->setCustomOption(self::OPTION_ICON, $iconCssClass);

        return $this;
    }
}
