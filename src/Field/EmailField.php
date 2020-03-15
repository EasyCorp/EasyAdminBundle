<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class EmailField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this
            ->setType('email')
            ->setFormType(EmailType::class)
            ->setTemplateName('crud/field/email');
    }
}
