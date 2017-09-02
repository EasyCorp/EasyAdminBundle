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
        $form = $event->getForm();

        if (null === $data = $event->getData()) {
            $data = array('autocomplete' => array());
            $event->setData($data);
        }

        $options = $form->get('autocomplete')->getConfig()->getOptions();
        $options['choices'] = $options['em']->getRepository($options['class'])->findBy(array(
            $options['id_reader']->getIdField() => $data['autocomplete'],
        ));

        if (isset($options['choice_list'])) {
            // clear choice list for SF < 3.0
            $options['choice_list'] = null;
        }

        $form->add('autocomplete', LegacyFormHelper::getType('entity'), $options);
    }
}

class_alias('JavierEguiluz\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminAutocompleteSubscriber', 'EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminAutocompleteSubscriber', false);
