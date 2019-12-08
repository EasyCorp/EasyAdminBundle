<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextProperty extends AbstractProperty
{
    private $maxLength = -1;

    public function __construct()
    {
        $this->type = 'text';
        $this->formType = TextType::class;
        $this->defaultTemplatePath = '@EasyAdmin/field_text.html.twig';
    }

    public function setCustomOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('maxLength')
            ->setAllowedTypes('maxLength', 'integer')
            ->setDefault('maxLength', -1);
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    /**
     * @param int $length -1 means no max length
     */
    public function setMaxLength(int $length): self
    {
        $this->maxLength = $length;

        return $this;
    }
}
