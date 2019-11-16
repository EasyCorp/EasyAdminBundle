<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class TextField extends AbstractField
{
    private $maxLength = INF;

    public function __construct()
    {
        $this
            ->setType('text')
            ->setFormType(TextType::class)
            ->setDefaultTemplatePath('@EasyAdmin/field_text.html.twig');
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    public function setMaxLength(int $length): self
    {
        $this->maxLength = $length;

        return $this;
    }
}
