<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This is a special form type used to render the form layout when using form columns.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal don't use this type in your applications
 */
class EaFormColumnGroupCloseType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('ea_is_inside_tab')->default(false)->allowedTypes('boolean')
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['ea_is_inside_tab'] = $options['ea_is_inside_tab'];
    }

    public function getBlockPrefix(): string
    {
        return 'ea_form_column_group_close';
    }
}
