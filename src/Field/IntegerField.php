<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

final class IntegerField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this
            ->setType('integer')
            ->setFormType(IntegerType::class)
            ->setTemplateName('crud/field/integer');
    }
}
