<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class IdField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this
            ->setType('id')
            ->setFormType(TextType::class)
            ->setTemplateName('crud/field/id');
    }
}
