<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Fixtures\UrlField;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

class ChildUrlType extends AbstractType
{
    public function getParent(): string
    {
        return UrlType::class;
    }
}
