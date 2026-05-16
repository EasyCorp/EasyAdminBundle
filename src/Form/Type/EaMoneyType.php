<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer\MoneyToNumericTransformer;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends MoneyType to support Money\Money objects via a data transformer.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EaMoneyType extends MoneyType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        if (true === $options['ea_money_object']) {
            $builder->addModelTransformer(new MoneyToNumericTransformer($options['currency']));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('ea_money_object', false);
        $resolver->setAllowedTypes('ea_money_object', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'money';
    }
}
