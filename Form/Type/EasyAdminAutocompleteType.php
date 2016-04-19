<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type;

use JavierEguiluz\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Autocomplete form type.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminAutocompleteType extends AbstractType
{
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
        $this->getBlockPrefix();
    }
}
