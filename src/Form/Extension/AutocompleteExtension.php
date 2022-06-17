<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Extension;

use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\CrudAutocompleteParentSubscriber;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

class AutocompleteExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CrudAutocompleteParentSubscriber());
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
