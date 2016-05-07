<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds some custom attributes to the request object to store information
 * related to EasyAdmin.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class RequestPostInitializeListener
{
    /** @var Request|null */
    private $request;

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
     * BC for SF < 2.4.
     * To be replaced by the usage of the request stack when 2.3 support is dropped.
     *
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Adds to the request some attributes with useful information, such as the
     * current entity and the selected item, if any.
     *
     * @param GenericEvent $event
     */
    public function initializeRequest(GenericEvent $event)
    {
        if (null !== $this->requestStack) {
            $this->request = $this->requestStack->getCurrentRequest();
        }

        if (null === $this->request) {
            return;
        }

        $this->request->attributes->set('easyadmin', array(
            'entity' => $entity = $event->getArgument('entity'),
            'view' => $this->request->query->get('action', 'list'),
            'item' => ($id = $this->request->query->get('id')) ? $this->findCurrentItem($entity, $id) : null,
        ));
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
     */
    private function findCurrentItem(array $entityConfig, $itemId)
    {
        $manager = $this->doctrine->getManagerForClass($entityConfig['class']);
        if (null === $entity = $manager->getRepository($entityConfig['class'])->find($itemId)) {
            throw new EntityNotFoundException(array('entity' => $entityConfig, 'entity_id' => $itemId));
        }

        return $entity;
    }
}
