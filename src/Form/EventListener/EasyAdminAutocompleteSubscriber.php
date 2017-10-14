<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminAutocompleteSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        );
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData() ?: array();

        $options = $form->getConfig()->getOptions();
        $options['compound'] = false;
        $options['choices'] = is_array($data) || $data instanceof \Traversable ? $data : array($data);

        $form->add('autocomplete', LegacyFormHelper::getType('entity'), $options);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $options = $form->get('autocomplete')->getConfig()->getOptions();

        if (!isset($data['autocomplete']) || '' === $data['autocomplete']) {
            $options['choices'] = array();
        } else {
            $options['choices'] = $options['em']->getRepository($options['class'])->findBy(array(
                $this->getIdField($options) => $data['autocomplete'],
            ));
        }

        // reset some critical lazy options
        unset($options['em'], $options['loader'], $options['empty_data'], $options['choice_list'], $options['choices_as_values']);

        $form->add('autocomplete', LegacyFormHelper::getType('entity'), $options);
    }

    private function getIdField(array $options)
    {
        if (isset($options['id_reader'])) {
            $idField = $options['id_reader']->getIdField();
        } else {
            // BC for 2.3
            $idField = current($options['em']->getClassMetadata($options['class'])->getIdentifierFieldNames());
        }

        return $idField;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminAutocompleteSubscriber', 'JavierEguiluz\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminAutocompleteSubscriber', false);
