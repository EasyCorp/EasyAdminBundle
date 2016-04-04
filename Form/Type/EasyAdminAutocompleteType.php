<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Autocomplete form type.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminAutocompleteType extends AbstractType
{
    private $preSetData = false;
    private $preSubmit = false;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->preSetData) {
            // avoid infinite call to PRE_SET_DATA event
            $this->preSetData = true;

            $preSetDataListener = function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $data = $event->getData();
                // settings selected data
                $options['choices'] = is_array($data) || $data instanceof Collection ? $data : array($data);
                // redefine form and choices option
                $form->getParent()->add($form->getName(), 'JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType', $options);
            };

            $builder->addEventListener(FormEvents::PRE_SET_DATA, $preSetDataListener);
        }

        if (!$this->preSubmit) {
            $self = $this;
            $preSubmitListener = function (FormEvent $event) use ($self, $options) {
                // avoid infinite call to PRE_SUBMIT event
                $self->preSubmit = true;

                $form = $event->getForm();
                $data = $event->getData();
                // normalize data choices
                $normData = $options['em']->getRepository($options['class'])->findBy(array(
                    $options['id_reader']->getIdField() => $data
                ));
                // settings selected data
                $options['choices'] = $normData;

                // redefine form and choices option
                $form->getParent()->add($form->getName(), 'JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType', $options);
                // submit data to new form
                $form->getParent()->get($form->getName())->submit($data);
            };

            $builder->addEventListener(FormEvents::PRE_SUBMIT, $preSubmitListener);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // this prevents the form field to load all the entity records from the database
            'choices' => array(),
        ));
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        // BC for Symfony < 3
        if (!method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            return 'entity';
        }

        return 'Symfony\Bridge\Doctrine\Form\Type\EntityType';
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
