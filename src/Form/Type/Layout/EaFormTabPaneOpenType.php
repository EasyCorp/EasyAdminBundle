<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This is a special form type used to render the form layout when using form tabs.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal don't use this type in your applications
 */
class EaFormTabPaneOpenType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('ea_tab_id')->allowedTypes('string')
            ->define('ea_css_class')->default(null)->allowedTypes('string', 'null')
            ->define('ea_help')->default(null)->allowedTypes(TranslatableInterface::class, 'string', 'null')
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['ea_tab_id'] = $options['ea_tab_id'];
        $view->vars['ea_css_class'] = $options['ea_css_class'];
        $view->vars['ea_help'] = $options['ea_help'];
    }

    public function getBlockPrefix(): string
    {
        return 'ea_form_tabpane_open';
    }
}
