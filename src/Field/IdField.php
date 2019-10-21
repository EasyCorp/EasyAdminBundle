<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdField extends AbstractField
{
    public function setCustomOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => 'id',
            'formType' => TextType::class,
            'templatePath' => '@EasyAdmin/default/field_id.html.twig',
        ]);
    }
}
