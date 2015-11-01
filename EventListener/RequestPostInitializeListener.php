<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\EntityNotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;

class RequestPostInitializeListener
{
    /** @var Request|null */
    private $request;

    /** @var Registry */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * BC for SF < 2.4.
     * To be replaced by the usage of the request stack when 2.3 support is dropped.
     *
     * @param Request|null $request
     *
     * @return $this
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    public function initializeRequest(GenericEvent $event)
    {
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
     * @param array $entity
     * @param mixed $id
     *
     * @return object The entity
     *
     * @throws EntityNotFoundException
     */
    private function findCurrentItem(array $entity, $id)
    {
        if (!$entity = $this->doctrine->getRepository($entity['class'])->find($id)) {
            throw new EntityNotFoundException(array('entity' => $entity, 'entity_id' => $id));
        }

        return $entity;
    }
}
