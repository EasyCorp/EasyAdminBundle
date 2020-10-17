<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The 'panel' form type is used to display a FIRST LEVEL design element
 * needed to create complex form layouts. This "fake" type just displays some HTML tags.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EaFormPanelType extends AbstractType
{
    protected const TYPE = 'panel';

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ea_form_'.self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setMapped(false);
        $resolver->setRequired(false);
        $resolver->setOption('allow_extra_fields', true);
    }
}
