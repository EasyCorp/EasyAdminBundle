<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type;

use JavierEguiluz\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Autocomplete form type.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminAutocompleteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $preSetDataListener = function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData() ?: array();

            // modify some of the settings inherited from the parent form type
            $options['compound'] = false;
            // normalize choices list
            $options['choices'] = is_array($data) || $data instanceof \Traversable ? $data : array($data);

            // create autocomplete form field
            $form->add('autocomplete', LegacyFormHelper::getType('entity'), $options);
        };

        $preSubmitListener = function (FormEvent $event) {
            $form = $event->getForm();

            if (null === $data = $event->getData()) {
                $data = array('autocomplete' => array());
                $event->setData($data);
            }

            // reuse autocomplete options, but replace initial choices with submitted data
            $options = $form->get('autocomplete')->getConfig()->getOptions();
            $options['choices'] = $options['em']->getRepository($options['class'])->findBy(array(
                $options['id_reader']->getIdField() => $data['autocomplete'],
            ));

            if (isset($options['choice_list'])) {
                // clear choice list for SF < 3.0
                $options['choice_list'] = null;
            }

            // reset autocomplete form field with new choices list
            $form->add('autocomplete', LegacyFormHelper::getType('entity'), $options);
        };

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, $preSetDataListener)
            ->addEventListener(FormEvents::PRE_SUBMIT, $preSubmitListener)
            ->addModelTransformer(new CallbackTransformer(
                // transforms an entity to compound array
                function ($entity) {
                    return array('autocomplete' => $entity);
                },
                // transforms a compound array to entity
                function (array $compound) {
                    return $compound['autocomplete'];
                }
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // Add a custom block prefix to inner field to ease theming:
        array_splice($view['autocomplete']->vars['block_prefixes'], -1, 0, 'easyadmin_autocomplete_inner');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'multiple' => false,
            // force display errors on this form field
            'error_bubbling' => false,
        ));

        $resolver->setRequired(array('class'));
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'easyadmin_autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
