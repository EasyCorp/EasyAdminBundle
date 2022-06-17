<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class CrudAutocompleteSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData() ?: [];

        $options = $form->getConfig()->getOptions();
        $options['compound'] = false;
        $options['choices'] = is_iterable($data) ? $data : [$data];
        unset($options['allow_add_new_entities'], $options['new_entities_handler']);

        $form->add('autocomplete', EntityType::class, $options);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $options = $form->get('autocomplete')->getConfig()->getOptions();

        if (!isset($data['autocomplete']) || '' === $data['autocomplete']) {
            $options['choices'] = [];
        } else {
            $options['choices'] = $options['em']->getRepository($options['class'])->findBy([
                $options['id_reader']->getIdField() => $data['autocomplete'],
            ]);
            if (true === $form->getConfig()->getOption('allow_add_new_entities')
                && \count($options['choices']) < \count($data['autocomplete'])) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();
                $newValues = array_diff(
                    $data['autocomplete'],
                    array_map(
                        static fn (object $associatedEntity) => $propertyAccessor
                            ->getValue($associatedEntity, $options['id_reader']->getIdField()),
                        $options['choices']
                    )
                );
                $parentListenersCallables = $form->getParent()
                    ->getConfig()
                    ->getEventDispatcher()
                    ->getListeners(FormEvents::POST_SUBMIT);
                foreach ($parentListenersCallables as $parentListenerCallable) {
                    if (\is_array($parentListenerCallable)
                        && isset($parentListenerCallable[0])
                        && $parentListenerCallable[0] instanceof CrudAutocompleteParentSubscriber) {
                        $parentListenerCallable[0]->setNewValuesAndHandler($newValues, $form->getConfig()->getOption('new_entities_handler'));
                    }
                }
                $data['autocomplete'] = array_diff($data['autocomplete'], $newValues);
                $event->setData($data);
            }
        }

        // reset some critical lazy options
        unset($options['em'], $options['loader'], $options['empty_data'], $options['choice_list'], $options['choices_as_values']);

        $form->add('autocomplete', EntityType::class, $options);
    }
}
