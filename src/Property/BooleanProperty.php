<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BooleanProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this
            ->setType('boolean')
            ->setFormType(ChoiceType::class)
            ->setTextAlign('center')
            ->setTemplateName('property/boolean');
    }
}
