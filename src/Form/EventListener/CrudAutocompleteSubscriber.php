<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\EventListener;

use Doctrine\ORM\Mapping\FieldMapping;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

        // apply custom choice_label if autocomplete is customized so the selected item looks the same as the other entries.
        // note: we don't escape here because Twig already escapes the <option> content automatically;
        // the renderAsHtml flag controls how TomSelect renders the item (via data-ea-autocomplete-render-items-as-html).
        $callback = $options['autocomplete_callback'] ?? null;
        $template = $options['autocomplete_template'] ?? null;

        if (null !== $template) {
            $twig = $this->twig;
            $options['choice_label'] = static function ($entity) use ($twig, $template): string {
                return $twig->render($template, ['entity' => $entity]);
            };
        } elseif (null !== $callback) {
            $options['choice_label'] = static function ($entity) use ($callback): string {
                return (string) $callback($entity);
            };
        }

        // remove custom options before passing to EntityType
        unset(
            $options['autocomplete_callback'],
            $options['autocomplete_template']
        );

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
                    static function ($v) use ($options) {
                        // TODO: replace 'ulid' by Symfony\Bridge\Doctrine\Types\UlidType::NAME when Symfony 5.4 is no longer supported
                        // TODO: replace 'uuid' by Symfony\Bridge\Doctrine\Types\UuidType::NAME when Symfony 5.4 is no longer supported
                        if (class_exists(Ulid::class) && Ulid::isValid($v) && self::checkNativeType('ulid', $options)) {
                            return Ulid::fromBase32($v)->toRfc4122();
                        } elseif (class_exists(Uuid::class) && Uuid::isValid($v) && self::checkNativeType('uuid', $options)) {
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

    /** @param array<string, mixed> $options */
    private static function checkNativeType(string $type, array $options): bool
    {
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

        return $type === $idFieldType;
    }
}
