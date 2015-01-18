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

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class AdminController extends Controller
{
    protected $allowedActions = array('list', 'edit', 'new', 'show', 'search', 'delete');
    protected $config;
    protected $entity = array();

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $em;

    /**
     * @Route("/", name="admin")
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

    protected function initialize(Request $request)
    {
        $this->config = $this->container->getParameter('easy_admin.config');

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

            $this->entity = $this->getEntityMetadata($entityName);
        }

        if (!$request->query->has('sortField')) {
            $request->query->set('sortField', 'id');
        }
        if (!$request->query->has('sortDirection') || !in_array(strtoupper($request->query->get('sortDirection')), array('ASC', 'DESC'))) {
            $request->query->set('sortDirection', 'DESC');
        }

        $this->request = $request;
    }

    public function listAction()
    {
        $fields = $this->getFieldsForList($this->entity['fieldMappings']);
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list_max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));

        return $this->render('@EasyAdmin/list.html.twig', array(
            'config'    => $this->config,
            'entity'    => $this->entity,
            'paginator' => $paginator,
            'fields'    => $fields,
        ));
    }

    public function editAction()
    {
        if (!$item = $this->em->getRepository($this->entity['class'])->find($this->request->query->get('id'))) {
            throw $this->createNotFoundException(sprintf('Unable to find entity (%s #%d).', $this->entity['name'], $this->request->query->get('id')));
        }

        $fields = $this->getFieldsForEdit($this->entity['fieldMappings']);
        $editForm = $this->createEditForm($item, $fields);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $this->request->query->get('id'));

        $editForm->handleRequest($this->request);
        if ($editForm->isValid()) {
            $item = $this->prepareEditEntityForPersist($item);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        return $this->render('@EasyAdmin/edit.html.twig', array(
            'config' => $this->config,
            'entity' => $this->entity,
            'form'   => $editForm->createView(),
            'item'   => $item,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    public function showAction()
    {
        if (!$item = $this->em->getRepository($this->entity['class'])->find($this->request->query->get('id'))) {
            throw $this->createNotFoundException(sprintf('Unable to find entity (%s #%d).', $this->entity['name'], $this->request->query->get('id')));
        }

        $fields = $this->getFieldsForShow($this->entity['fieldMappings']);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $this->request->query->get('id'));

        return $this->render('@EasyAdmin/show.html.twig', array(
            'config' => $this->config,
            'entity' => $this->entity,
            'item'   => $item,
            'fields' => $fields,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    public function newAction()
    {
        $entityFullyQualifiedClassName = $this->entity['class'];
        $item = new $entityFullyQualifiedClassName();

        $fields = $this->getFieldsForNew($this->entity['fieldMappings']);
        $newForm = $this->createNewForm($item, $fields);

        $newForm->handleRequest($this->request);
        if ($newForm->isValid()) {
            $item = $this->prepareNewEntityForPersist($item);
            $this->em->persist($item);
            $this->em->flush();

            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'entity' => $this->entity['name'])));
        }

        return $this->render('@EasyAdmin/new.html.twig', array(
            'config' => $this->config,
            'entity' => $this->entity,
            'form'   => $newForm->createView(),
            'item'   => $item,
        ));
    }

    public function deleteAction()
    {
        if ('DELETE' !== $this->request->query->getMethod()) {
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

    public function searchAction()
    {
        $searchableFields = $this->getSearchableFields($this->entity['fieldMappings']);
        $paginator = $this->findBy($this->entity['class'], $this->request->query->get('query'), $searchableFields, $this->request->query->get('page', 1), $this->config['list_max_results']);
        $fields = $this->getFieldsForSearch($this->entity['fieldMappings']);

        return $this->render('@EasyAdmin/list.html.twig', array(
            'config'    => $this->config,
            'entity'    => $this->entity,
            'paginator' => $paginator,
            'fields'    => $fields,
        ));
    }

    /**
     * Takes the FQCN of the Doctrine entity and returns all its configured metadata.
     */
    protected function getEntityMetadata($entityName)
    {
        $entityMetadata = array();

        $entityMetadata['name'] = $entityName;
        $entityMetadata['class'] = $this->config['entities'][$entityName]['class'];

        $doctrineMetadata = $this->em->getMetadataFactory()->getMetadataFor($entityMetadata['class']);

        // TODO: Check if the entity performs any kind of inheritance: $doctrineMetadata->isInheritanceTypeNone()

        if ('id' !== $doctrineMetadata->identifier[0]) {
            throw new \RuntimeException(sprintf("The '%s' entity isn't valid because it doesn't define a primary key called 'id'.", $entityMetadata['class']));
        }

        // add regular entity fields
        foreach ($doctrineMetadata->fieldMappings as $fieldName => $fieldMetadata) {
            // field names must be processed to avoid problems in Twig templates
            $fieldName = str_replace('_', '', $fieldName);

            $entityMetadata['fieldMappings'][$fieldName] = $fieldMetadata;
        }

        // add fields for entity associations (except many-to-many)
        foreach ($doctrineMetadata->associationMappings as $fieldName => $associationMetadata) {
            if (ClassMetadataInfo::MANY_TO_MANY !== $associationMetadata['type']) {
                $entityMetadata['fieldMappings'][$fieldName] = array(
                    'association'  => $associationMetadata['type'],
                    'fieldName'    => $fieldName,
                    'fetch'        => $associationMetadata['fetch'],
                    'isOwningSide' => $associationMetadata['isOwningSide'],
                    'type'         => 'association',
                    'targetEntity' => $associationMetadata['targetEntity'],
                );
            }
        }

        return $entityMetadata;
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * edited before persisting it.
     */
    protected function prepareEditEntityForPersist($entity)
    {
        return $entity;
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * created before persisting it.
     */
    protected function prepareNewEntityForPersist($entity)
    {
        return $entity;
    }

    /**
     * These are the entity fields on which the query is performed.
     */
    protected function getSearchableFields(array $entityFields)
    {
        $excludedFieldNames = array();
        $excludedFieldTypes = array('association', 'binary', 'blob', 'date', 'datetime', 'datetimetz', 'guid', 'time', 'object');

        return $this->filterEntityFieldsBasedOnNameAndTypeBlackList($entityFields, $excludedFieldNames, $excludedFieldTypes);
    }

    /**
     * These are the entity fields displayed in the listings.
     */
    protected function getFieldsForList(array $entityFields)
    {
        $entityConfiguration = $this->config['entities'][$this->entity['name']];

        if (array_key_exists('list', $entityConfiguration) && array_key_exists('fields', $entityConfiguration['list'])) {
            return $this->filterEntityFieldsBasedOnWhitelist($entityFields, $entityConfiguration['list']['fields']);
        } else {
            return $this->filterListFieldsBasedOnSmartGuesses($entityFields);
        }
    }

    protected function filterEntityFieldsBasedOnWhitelist(array $fields, array $whiteList)
    {
        $filteredFields = array();

        foreach ($whiteList as $fieldName) {
            if (array_key_exists($fieldName, $fields)) {
                // these are the real fields defined in the Entity configuration
                // just copy the mapping information provided by Doctrine
                $filteredFields[$fieldName] = $fields[$fieldName];
            } else {
                // these fields aren't real entity properties but methods
                // they are used to display 'virtual fields' based on
                // entity methods (e.g. public function getFullName() { return $this->name.' '.$this->surname; } )
                $filteredFields[$fieldName] = array(
                    'fieldName' => $fieldName,
                    'type'      => 'virtual',
                );
            }
        }

        return $filteredFields;
    }

    protected function filterEntityFieldsBasedOnNameAndTypeBlackList(array $fields, array $fieldNameBlackList, array $fieldTypeBlackList)
    {
        $filteredFields = array();

        foreach ($fields as $name => $metadata) {
            if (!in_array($name, $fieldNameBlackList) && !in_array($metadata['type'], $fieldTypeBlackList)) {
                $filteredFields[$name] = $fields[$name];
            }
        }

        return $filteredFields;
    }

    protected function filterListFieldsBasedOnSmartGuesses(array $fields)
    {
        // empirical guess: listings with more than 8 fields look ugly
        $maxListFields = 8;
        $excludedFieldNames = array('slug', 'password', 'salt', 'updatedAt');
        $excludedFieldTypes = array('array', 'binary', 'blob', 'guid', 'json_array', 'object', 'simple_array', 'text');

        // if the entity has few fields, show them all
        if (count($fields) <= $maxListFields) {
            return $fields;
        }

        // if the entity has a lot of fields, try to guess which fields we can remove
        $filteredFields = $fields;
        foreach ($fields as $name => $metadata) {
            if (in_array($name, $excludedFieldNames) || in_array($metadata['type'], $excludedFieldTypes)) {
                unset($filteredFields[$name]);

                // whenever a field is removed, check again if we are below the acceptable number of fields
                if (count($filteredFields) <= $maxListFields) {
                    return $filteredFields;
                }
            }
        }

        // if the entity has still a lot of remaining fields, just slice the last ones
        return array_slice($filteredFields, 0, $maxListFields);
    }

    /**
     * These are the entity fields displayed in the 'show' action.
     */
    protected function getFieldsForShow(array $entityFields)
    {
        return $entityFields;
    }

    /**
     * These are the fields displayed in the search results listings
     */
    protected function getFieldsForSearch(array $entityFields)
    {
        return $this->getFieldsForList($entityFields);
    }

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

    protected function createEditForm($entity, array $entityFieldsMapping)
    {
        $formTypeMap = array(
            'boolean' => 'checkbox',
            'datetime' => 'datetime',
            'datetimetz' => 'datetime',
            'text' => 'textarea',
        );

        $form = $this->createFormBuilder($entity, array(
            'data_class' => $this->entity['class'],
        ));

        foreach ($entityFieldsMapping as $name => $metadata) {
            if ('association' === $metadata['type'] && !$metadata['isOwningSide']) {
                continue;
            }

            $formFieldType = array_key_exists($metadata['type'], $formTypeMap)
                ? $formTypeMap[$metadata['type']]
                : null;

            $form->add($name, $formFieldType, array());
        }

        return $form->getForm();
    }

    /**
     * These are the entity fields included in the form displayed for the 'edit' action.
     */
    protected function getFieldsForEdit(array $entityFields)
    {
        $entityConfiguration = $this->config['entities'][$this->entity['name']];

        if (array_key_exists('edit', $entityConfiguration) && array_key_exists('fields', $entityConfiguration['edit'])) {
            return $this->filterEntityFieldsBasedOnWhitelist($entityFields, $entityConfiguration['edit']['fields']);
        }

        $excludedFieldNames = array('id');
        $excludedFieldTypes = array('binary', 'blob', 'json_array', 'object');

        return $this->filterEntityFieldsBasedOnNameAndTypeBlackList($entityFields, $excludedFieldNames, $excludedFieldTypes);
    }

    protected function getFieldsForNew(array $entityFields)
    {
        $entityConfiguration = $this->config['entities'][$this->entity['name']];

        if (array_key_exists('new', $entityConfiguration) && array_key_exists('fields', $entityConfiguration['new'])) {
            return $this->filterEntityFieldsBasedOnWhitelist($entityFields, $entityConfiguration['new']['fields']);
        }

        $excludedFieldNames = array('id');
        $excludedFieldTypes = array();

        return $this->filterEntityFieldsBasedOnNameAndTypeBlackList($entityFields, $excludedFieldNames, $excludedFieldTypes);
    }

    protected function createNewForm($entity, array $entityFieldsMapping)
    {
        return $this->createEditForm($entity, $entityFieldsMapping);
    }

    protected function getNameOfTheFirstConfiguredEntity()
    {
        $entityNames = array_keys($this->config['entities']);

        return $entityNames[0];
    }

    protected function createDeleteForm($entityName, $entityId)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin', array('action' => 'delete', 'entity' => $entityName, 'id' => $entityId)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

    protected function render404error($view, array $parameters = array())
    {
        return $this->render($view, $parameters, new Response('', 404));
    }
}
