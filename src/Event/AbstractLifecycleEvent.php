<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Event\EntityLifecycleEventInterface;

/**
 * @author: Benjamin Leibinger <mail@leibinger.io>
 *
 * @template TEntity of object
 *
 * @implements EntityLifecycleEventInterface<TEntity>
 */
abstract class AbstractLifecycleEvent implements EntityLifecycleEventInterface
{
    /**
     * @var TEntity
     */
    protected $entityInstance;

    /**
     * @param TEntity $entityInstance
     */
    public function __construct(/* object */ $entityInstance)
    {
        if (!\is_object($entityInstance)) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$entityInstance',
                __METHOD__,
                '"object"',
                \gettype($entityInstance)
            );
        }

        $this->entityInstance = $entityInstance;
    }

    public function getEntityInstance()/* : object */
    {
        return $this->entityInstance;
    }
}
