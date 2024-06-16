<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This is a special form type used to render the form layout when using form fieldsets.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal don't use this type in your applications
 */
class EaFormFieldsetOpenType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('ea_css_class')->default(null)->allowedTypes('string', 'null')
            ->define('ea_icon')->default(null)->allowedTypes('string', 'null')
            ->define('ea_help')->default(null)->allowedTypes(TranslatableInterface::class, 'string', 'null')
            ->define('ea_is_collapsible')->default(false)->allowedTypes('boolean')
            ->define('ea_is_collapsed')->default(false)->allowedTypes('boolean')
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['ea_css_class'] = $options['ea_css_class'];
        $view->vars['ea_icon'] = $options['ea_icon'];
        $view->vars['ea_help'] = $options['ea_help'];
        $view->vars['ea_is_collapsible'] = $options['ea_is_collapsible'];
        $view->vars['ea_is_collapsed'] = $options['ea_is_collapsed'];
    }

    public function getBlockPrefix(): string
    {
        return 'ea_form_fieldset_open';
    }
}
