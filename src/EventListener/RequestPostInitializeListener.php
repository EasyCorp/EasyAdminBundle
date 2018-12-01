<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds some custom attributes to the request object to store information
 * related to EasyAdmin.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class RequestPostInitializeListener
{
    /** @var RequestStack|null */
    private $requestStack;

    /** @var Registry */
    private $doctrine;

    /**
     * @param Registry          $doctrine
     * @param RequestStack|null $requestStack
     */
    public function __construct(Registry $doctrine, RequestStack $requestStack = null)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
    }

    /**
     * Adds to the request some attributes with useful information, such as the
     * current entity and the selected item, if any.
     *
     * @param GenericEvent $event
     */
    public function initializeRequest(GenericEvent $event)
    {
        $request = null;
        if (null !== $this->requestStack) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if (null === $request) {
            return;
        }

        $request->attributes->set('easyadmin', [
            'entity' => $entity = $event->getArgument('entity'),
            'view' => $request->query->get('action', 'list'),
            'item' => ($id = $request->query->get('id')) ? $this->findCurrentItem($entity, $id) : null,
        ]);
    }

    /**
     * Looks for the object that corresponds to the selected 'id' of the current entity.
     *
     * @param array $entityConfig
     * @param mixed $itemId
     *
     * @return object The entity
     *
     * @throws EntityNotFoundException
     * @throws \RuntimeException
     */
    private function findCurrentItem(array $entityConfig, $itemId)
    {
        if (null === $manager = $this->doctrine->getManagerForClass($entityConfig['class'])) {
            throw new \RuntimeException(\sprintf('There is no Doctrine Entity Manager defined for the "%s" class', $entityConfig['class']));
        }

        if (null === $entity = $manager->getRepository($entityConfig['class'])->find($itemId)) {
            throw new EntityNotFoundException(['entity_name' => $entityConfig['name'], 'entity_id_name' => $entityConfig['primary_key_field_name'], 'entity_id_value' => $itemId]);
        }

        return $entity;
    }
}
