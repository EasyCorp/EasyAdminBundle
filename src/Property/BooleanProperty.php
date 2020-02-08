<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/*
 * TODO: create a BooleanConfigurator to check if user can edit the entity.
 * If they can't, then disable the 'renderAsSwitch' option and make it false.
 */
class BooleanProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public const OPTION_RENDER_AS_SWITCH = 'renderAsSwitch';

    public function __construct()
    {
        $this
            ->setType('boolean')
            ->setFormType(CheckboxType::class)
            ->setTextAlign('center')
            ->setTemplateName('property/boolean')
            ->setCustomOption(self::OPTION_RENDER_AS_SWITCH, true);
    }

    public function renderAsSwitch(bool $isASwitch = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_SWITCH, $isASwitch);

        return $this;
    }
}
