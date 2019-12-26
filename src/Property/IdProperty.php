<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class IdProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this
            ->setType('id')
            ->setFormType(TextType::class)
            ->setTemplateName('property/id');
    }
}
