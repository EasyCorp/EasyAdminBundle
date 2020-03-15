<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TelType;

class TelephoneField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this
            ->setType('telephone')
            ->setFormType(TelType::class)
            ->setTemplateName('crud/field/telephone');
    }
}
