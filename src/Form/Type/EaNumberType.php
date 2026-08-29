<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\StringToBcMathNumberTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends NumberType to support \BcMath\Number objects via a data transformer.
 */
class EaNumberType extends NumberType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        if (true === $options['ea_bcmath_number']) {
            $builder->addModelTransformer(new StringToBcMathNumberTransformer());
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('ea_bcmath_number', false);
        $resolver->setAllowedTypes('ea_bcmath_number', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'number';
    }
}
