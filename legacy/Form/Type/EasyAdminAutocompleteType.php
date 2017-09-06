<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminAutocompleteType class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType');

if (\false) {
    class EasyAdminAutocompleteType extends AbstractType implements DataMapperInterface
    {
    }
}
