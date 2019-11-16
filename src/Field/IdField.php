<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class IdField extends AbstractField
{
    public function __construct()
    {
        $this
            ->setType('id')
            ->setFormType(TextType::class)
            ->setDefaultTemplatePath('@EasyAdmin/field_id.html.twig');
    }
}
