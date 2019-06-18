<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Fixtures;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\FilterType;
use Symfony\Component\Form\FormInterface;

class FooFilterType extends FilterType
{
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
    }
}
