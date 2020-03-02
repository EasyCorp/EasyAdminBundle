<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormPanelType;

final class FormPanelProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_ICON = 'icon';

    public function __construct()
    {
        $this
            ->setType('form_panel')
            ->setFormType(EaFormPanelType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->setTemplateName('property/form_panel')
            ->setCustomOption(self::OPTION_ICON, null);
    }

    public function setIcon(string $iconCssClass): self
    {
        $this->setCustomOption(self::OPTION_ICON, $iconCssClass);

        return $this;
    }
}
