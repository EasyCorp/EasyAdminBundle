<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class IdProperty extends AbstractProperty
{
    public function __construct()
    {
        $this->type = 'id';
        $this->formType = TextType::class;
        $this->defaultTemplatePath = '@EasyAdmin/field_id.html.twig';
    }
}
