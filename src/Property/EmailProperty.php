<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class EmailProperty implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this
            ->setType('email')
            ->setFormType(EmailType::class)
            ->setTemplateName('property/email');
    }
}
