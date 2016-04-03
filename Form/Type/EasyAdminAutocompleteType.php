<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EasyAdminAutocompleteType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'class'
        ));
    }

    public function getParent()
    {
        // BC for Symfony < 3
        if (!method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            return 'entity';
        }

        return 'Symfony\Bridge\Doctrine\Form\Type\EntityType';
    }

    public function getName()
    {
        return 'easyadmin_autocomplete';
    }

    public function getBlockPrefix()
    {
        return $this->getName();
    }
}
