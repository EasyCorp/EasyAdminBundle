<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextField extends AbstractField
{
    private $maxLength;

    public function setCustomOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined([
            'maxLength',
        ])->setDefaults([
            'type' => 'text',
            'formType' => TextType::class,
            'maxLength' => -1,
            'templatePath' => '@EasyAdmin/default/field_text.html.twig',
        ]);
    }

    public function maxLength(int $length): self
    {
        $this->maxLength = $length;

        return $this;
    }
}
