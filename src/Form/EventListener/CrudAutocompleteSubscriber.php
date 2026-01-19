<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use Doctrine\ORM\Mapping\FieldMapping;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class CrudAutocompleteSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * @return void
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData() ?? [];

        $options = $form->getConfig()->getOptions();
        $options['compound'] = false;
        $options['choices'] = is_iterable($data) ? $data : [$data];

        $form->add('autocomplete', EntityType::class, $options);
    }

    /**
     * @return void
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $options = $form->get('autocomplete')->getConfig()->getOptions();

        if (!isset($data['autocomplete']) || '' === $data['autocomplete']) {
            $options['choices'] = [];
        } else {
            if (false === $options['id_reader']->isIntId()) {
                if (!\is_array($data['autocomplete'])) {
                    $data['autocomplete'] = [$data['autocomplete']];
                }

                $data['autocomplete'] = array_map(
                    function ($v) use ($options) {
                        if (class_exists(Ulid::class) && Ulid::isValid($v)) {
                            return Ulid::fromBase32($v)->toRfc4122();
                        } elseif (class_exists(Uuid::class) && Uuid::isValid($v)) {
                            // checking the mapping, as uuid can also be used as simple string
                            // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
                            $idFieldMapping = $options['em']->getClassMetadata($options['class'])->getFieldMapping($options['id_reader']->getIdField());
                            // In Doctrine ORM 2.x, getFieldMapping() returns an array
                            /** @phpstan-ignore-next-line function.impossibleType */
                            if (\is_array($idFieldMapping)) {
                                /** @phpstan-ignore-next-line cast.useless */
                                $idFieldMapping = (object) $idFieldMapping;
                            }
                            /** @phpstan-ignore-next-line function.alreadyNarrowedType */
                            $idFieldType = property_exists($idFieldMapping, 'type') ? $idFieldMapping->type : $idFieldMapping['type'];

                            if (UuidType::NAME === $idFieldType) {
                                return Uuid::fromString($v)->toBinary();
                            }
                        }

                        return $v;
                    },
                    $data['autocomplete']
                );
            }

            $options['choices'] = $options['em']->getRepository($options['class'])->findBy([
                $options['id_reader']->getIdField() => $data['autocomplete'],
            ]);
        }

        // reset some critical lazy options
        unset($options['em'], $options['loader'], $options['empty_data'], $options['choice_list'], $options['choices_as_values']);

        $form->add('autocomplete', EntityType::class, $options);
    }
}
