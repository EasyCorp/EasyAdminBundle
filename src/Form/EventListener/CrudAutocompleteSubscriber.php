<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use Doctrine\DBAL\Types\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class CrudAutocompleteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData() ?? [];

        $options = $form->getConfig()->getOptions();
        $options['compound'] = false;
        $options['choices'] = is_iterable($data) ? $data : [$data];

        // strip EA-specific options before forwarding to EntityType
        $callback = $options['autocomplete_callback'] ?? null;
        $template = $options['autocomplete_template'] ?? null;
        unset($options['autocomplete_callback'], $options['autocomplete_template']);

        // Resolve choice_label:
        // - if the user supplied one (via value_type_options.choice_label), keep it;
        // - otherwise derive one from autocomplete_template / autocomplete_callback so the
        //   selected item matches the rendering of other entries in the dropdown;
        // - otherwise drop the option entirely so EntityType falls back to __toString().
        //
        // Note: we don't escape here because Twig already escapes the <option> content
        // automatically; the renderAsHtml flag controls how TomSelect renders the item
        // (via data-ea-autocomplete-render-items-as-html).
        if (null === ($options['choice_label'] ?? null)) {
            if (null !== $template) {
                $twig = $this->twig;
                $options['choice_label'] = static fn ($entity): string => $twig->render($template, ['entity' => $entity]);
            } elseif (null !== $callback) {
                $options['choice_label'] = static fn ($entity): string => (string) $callback($entity);
            } else {
                unset($options['choice_label']);
            }
        }

        $form->add('autocomplete', EntityType::class, $options);
    }

    public function preSubmit(FormEvent $event): void
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

                // for performance reasons: resolve the Doctrine field type once before mapping values
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

                // the 'uuid' and 'ulid' Doctrine type names can be claimed by third-party types which use
                // a different storage format (e.g. ramsey/uuid-doctrine stores UUIDs as CHAR(36) strings),
                // so convert the submitted values only when the registered type is the Symfony one, whose
                // storage format is the one assumed by the conversion made below; otherwise, pass the values
                // through unchanged and let the registered type convert them when running the query
                $idFieldDoctrineType = Type::hasType($idFieldType) ? Type::getType($idFieldType) : null;
                $isSymfonyUlidType = null === $idFieldDoctrineType || $idFieldDoctrineType instanceof UlidType;
                $isSymfonyUuidType = null === $idFieldDoctrineType || $idFieldDoctrineType instanceof UuidType;

                $data['autocomplete'] = array_map(
                    static function ($v) use ($options, $idFieldType, $isSymfonyUlidType, $isSymfonyUuidType) {
                        // TODO: replace 'ulid' by Symfony\Bridge\Doctrine\Types\UlidType::NAME when Symfony 5.4 is no longer supported
                        if ('ulid' === $idFieldType && $isSymfonyUlidType && class_exists(Ulid::class) && Ulid::isValid($v)) {
                            return Ulid::fromBase32($v)->toRfc4122();
                        }

                        // TODO: replace 'uuid' by Symfony\Bridge\Doctrine\Types\UuidType::NAME when Symfony 5.4 is no longer supported
                        if ('uuid' === $idFieldType && $isSymfonyUuidType && class_exists(Uuid::class) && Uuid::isValid($v)) {
                            // Use RFC4122 format for platforms with native GUID type (e.g., PostgreSQL),
                            // and binary format for platforms without native GUID type (e.g., MySQL, SQLite)
                            $platform = $options['em']->getConnection()->getDatabasePlatform();
                            $hasNativeGuidType = $platform->getGuidTypeDeclarationSQL([]) !== $platform->getStringTypeDeclarationSQL(['fixed' => true, 'length' => 36]);

                            return $hasNativeGuidType
                                ? Uuid::fromString($v)->toRfc4122()
                                : Uuid::fromString($v)->toBinary();
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
