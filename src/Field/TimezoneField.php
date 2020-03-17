<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

final class TimezoneField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this
            ->setType('timezone')
            ->setFormType(TimezoneType::class)
            ->setTemplateName('crud/field/timezone');
    }
}
