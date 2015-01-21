<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Some parts of this file are copied and/or inspired by the
 * DoctrineCRUDGenerator included in the SensioGeneratorBundle.
 *   License: MIT License
 *   Copyright: (c) Fabien Potencier <fabien@symfony.com>
 *   Source: https://github.com/sensiolabs/SensioGeneratorBundle
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

/**
 * Class AdminController
 */
class AdminController extends Controller
{
    protected $allowedActions = array('list', 'edit', 'new', 'show', 'search', 'delete');
    protected $config;
    protected $entity = array();

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $em;

    /**
     * @Route("/", name="admin")
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function indexAction(Request $request)
    {
        $result = $this->initialize($request);

        // initialize() returns a Response object when an error occurs.
        // This allows to display a detailed error message.
        if ($result instanceof Response) {
            return $result;
        }

        $action = $request->query->get('action', 'list');

        // for now, the homepage redirects to the 'list' action of the first entity
        if (null === $request->query->get('entity')) {
            return $this->redirect($this->generateUrl('admin', array('action' => $action, 'entity' => $this->getNameOfTheFirstConfiguredEntity())));
        }

        return $this->{$action.'Action'}();
    }

    /**
     * @param Request $request
     *
     * @return Response|null
     */
    protected function initialize(Request $request)
    {
        $this->config = $this->container->getParameter('easyadmin.config');

        if (0 === count($this->config['entities'])) {
            return $this->render404error('@EasyAdmin/error/no_entities.html.twig');
        }

        if (!in_array($action = $request->query->get('action', 'list'), $this->allowedActions)) {
            return $this->render404error('@EasyAdmin/error/forbidden_action.html.twig', array(
                'action' => $action,
                'allowed_actions' => $this->allowedActions,
            ));
        }

        $this->em = $this->getDoctrine()->getManager();

        if (null !== $entityName = $request->query->get('entity')) {
            if (!array_key_exists($entityName, $this->config['entities'])) {
                return $this->render404error('@EasyAdmin/error/undefined_entity.html.twig', array('entity_name' => $entityName));
            }

            $this->entity = $this->get('easyadmin.configurator')->getEntityConfiguration($entityName);
        }

        if (!$request->query->has('sortField')) {
            $request->query->set('sortField', 'id');
        }
        if (!$request->query->has('sortDirection') || !in_array(strtoupper($request->query->get('sortDirection')), array('ASC', 'DESC'))) {
            $request->query->set('sortDirection', 'DESC');
        }

        $this->request = $request;
    }

    /**
     * @return Response
     */
    protected function listAction()
    {
        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list_max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));

        return $this->render('@EasyAdmin/list.html.twig', array(
            'config'    => $this->config,
            'entity'    => $this->entity,
            'paginator' => $paginator,
            'fields'    => $fields,
        ));
    }

    /**
     * @return RedirectResponse|Response
     */
    protected function editAction()
    {
        if (!$item = $this->em->getRepository($this->entity['class'])->find($this->request->query->get('id'))) {
            throw $this->createNotFoundException(sprintf('Unable to find entity (%s #%d).', $this->entity['name'], $this->request->query->get('id')));
        }

        $fields = $this->entity['edit']['fields'];
        $editForm = $this->createEditForm($item, $fields);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $this->request->query->get('id'));

        $editForm->handleRequest($this->request);
        if ($editForm->isValid()) {
            $this->prepareEditEntityForPersist($item);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        return $this->render('@EasyAdmin/edit.html.twig', array(
            'config'        => $this->config,
            'entity'        => $this->entity,
            'form'          => $editForm->createView(),
            'entity_fields' => $fields,
            'item'          => $item,
            'delete_form'   => $deleteForm->createView(),
        ));
    }

    /**
     * @return Response
     */
    protected function showAction()
    {
        if (!$item = $this->em->getRepository($this->entity['class'])->find($this->request->query->get('id'))) {
            throw $this->createNotFoundException(sprintf('Unable to find entity (%s #%d).', $this->entity['name'], $this->request->query->get('id')));
        }

        $fields = $this->getFieldsForShow($this->entity['properties']);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $this->request->query->get('id'));

        return $this->render('@EasyAdmin/show.html.twig', array(
            'config' => $this->config,
            'entity' => $this->entity,
            'item'   => $item,
            'fields' => $fields,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @return RedirectResponse|Response
     */
    protected function newAction()
    {
        $entityFullyQualifiedClassName = $this->entity['class'];
        $item = new $entityFullyQualifiedClassName();

        $fields = $fields = $this->entity['new']['fields'];
        $newForm = $this->createNewForm($item, $fields);

        $newForm->handleRequest($this->request);
        if ($newForm->isValid()) {
            $this->prepareNewEntityForPersist($item);
            $this->em->persist($item);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        return $this->render('@EasyAdmin/new.html.twig', array(
            'config'        => $this->config,
            'entity'        => $this->entity,
            'form'          => $newForm->createView(),
            'entity_fields' => $fields,
            'item'          => $item,
        ));
    }

    /**
     * @return RedirectResponse
     */
    protected function deleteAction()
    {
        if ('DELETE' !== $this->request->getMethod()) {
            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        $form = $this->createDeleteForm($this->entity['name'], $this->request->query->get('id'));
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            if (!$entity = $this->em->getRepository($this->entity['class'])->find($this->request->query->get('id'))) {
                throw $this->createNotFoundException('The entity to be delete does not exist.');
            }

            $this->em->remove($entity);
            $this->em->flush();
        }

        return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'entity' => $this->entity['name'])));
    }

    /**
     * @return Response
     */
    protected function searchAction()
    {
        $searchableFields = $this->entity['search']['fields'];
        $paginator = $this->findBy($this->entity['class'], $this->request->query->get('query'), $searchableFields, $this->request->query->get('page', 1), $this->config['list_max_results']);
        $fields = $this->entity['list']['fields'];

        return $this->render('@EasyAdmin/list.html.twig', array(
            'config'    => $this->config,
            'entity'    => $this->entity,
            'paginator' => $paginator,
            'fields'    => $fields,
        ));
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * edited before persisting it.
     *
     * @param  object $entity
     * @return object
     */
    protected function prepareEditEntityForPersist($entity)
    {
        return $entity;
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * created before persisting it.
     *
     * @param  object $entity
     * @return object
     */
    protected function prepareNewEntityForPersist($entity)
    {
        return $entity;
    }

    /**
     * These are the entity fields displayed in the 'show' action.
     *
     * @param array $entityFields
     *
     * @return array
     */
    protected function getFieldsForShow(array $entityFields)
    {
        return $entityFields;
    }

    /**
     * @param string $entityClass
     * @param int    $page
     * @param int    $maxPerPage
     * @param string $sortField
     * @param string $sortDirection
     *
     * @return Pagerfanta
     */
    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null)
    {
        $query = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($entityClass, 'entity')
        ;

        if (null !== $sortField) {
            if (!$sortDirection || !in_array(strtoupper($sortDirection), array('ASC', 'DESC'))) {
                $sortDirection = 'DESC';
            }

            $query->orderBy('entity.'.$sortField, $sortDirection);
        }

        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * @param string $entityClass
     * @param string $searchQuery
     * @param array  $searchableFields
     * @param int    $page
     * @param int    $maxPerPage
     *
     * @return Pagerfanta
     */
    protected function findBy($entityClass, $searchQuery, array $searchableFields, $page = 1, $maxPerPage = 15)
    {
        $query = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($entityClass, 'entity')
        ;

        foreach ($searchableFields as $name => $metadata) {
            $query->orWhere("entity.$name LIKE :query")->setParameter('query', '%'.$searchQuery.'%');
        }

        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * @param object $entity
     * @param array  $entityProperties
     *
     * @return Form
     */
    protected function createEditForm($entity, array $entityProperties)
    {
        $form = $this->createFormBuilder($entity, array(
            'data_class' => $this->entity['class'],
        ));

        foreach ($entityProperties as $name => $metadata) {
            $formFieldOptions = array();

            if (array_key_exists('association', $metadata) && in_array($metadata['association'], array(ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY))) {
                continue;
            }

            if ('collection' === $metadata['type']) {
                $formFieldOptions = array(
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                );
            }

            $form->add($name, $metadata['type'], $formFieldOptions);
        }

        return $form->getForm();
    }

    /**
     * @param object $entity
     * @param array  $entityProperties
     *
     * @return Form
     */
    protected function createNewForm($entity, array $entityProperties)
    {
        return $this->createEditForm($entity, $entityProperties);
    }

    /**
     * @return mixed
     */
    protected function getNameOfTheFirstConfiguredEntity()
    {
        $entityNames = array_keys($this->config['entities']);

        return $entityNames[0];
    }

    /**
     * @param string  $entityName
     * @param integer $entityId
     *
     * @return Form
     */
    protected function createDeleteForm($entityName, $entityId)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin', array('action' => 'delete', 'entity' => $entityName, 'id' => $entityId)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

    /**
     * @param string $view
     * @param array  $parameters
     *
     * @return Response
     */
    protected function render404error($view, array $parameters = array())
    {
        return $this->render($view, $parameters, new Response('', 404));
    }
}
