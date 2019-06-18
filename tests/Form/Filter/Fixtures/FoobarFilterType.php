<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Fixtures;

use Symfony\Component\Form\AbstractType;

class FoobarFilterType extends AbstractType
{
    public function getParent()
    {
        return FooFilterType::class;
    }
}
