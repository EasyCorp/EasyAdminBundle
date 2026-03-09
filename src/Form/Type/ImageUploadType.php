<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ImageUploadType extends FileUploadType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        unset($options['show_image_preview']);

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'show_image_preview' => true,
        ]);

        $resolver->setAllowedTypes('show_image_preview', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['show_image_preview'] = $options['show_image_preview'];
    }

    public function getBlockPrefix(): string
    {
        return 'ea_imageupload';
    }
}
