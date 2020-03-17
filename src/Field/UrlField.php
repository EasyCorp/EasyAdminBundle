<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

final class UrlField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this
            ->setType('url')
            ->setFormType(UrlType::class)
            ->setTemplateName('crud/field/url');
    }
}
