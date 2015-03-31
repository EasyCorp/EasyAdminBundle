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
 * Class AdminController.
 */
class AdminController extends Controller
{
    protected $config;
    protected $entity = array();

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $em;

    /**
     * @Route("/", name="admin")
     *
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
        $view = $request->query->get('view', 'list');

        if (true !== $forbiddenActionResponse = $this->isActionAllowed($action, $view)) {
            return $forbiddenActionResponse;
        }

        // for now, the homepage redirects to the 'list' action and view of the first entity
        if (null === $request->query->get('entity')) {
            return $this->redirect($this->generateUrl('admin', array(
                'action' => $action,
                'entity' => $this->getNameOfTheFirstConfiguredEntity(),
                'view'   => $view,
            )));
        }

        return $this->{$action.'Action'}();
    }

    /**
     * Utility method which initializes the configuration of the entity on which
     * the user is performing the action.
     *
     * If everything goes right, it returns null. If there is any error, it
     * returns a 404 error page using a Response object.
     *
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

        // this condition happens when accessing the backend homepage, which
        // then redirects to the 'list' action of the first configured entity
        if (null === $entityName = $request->query->get('entity')) {
            return;
        }

        if (!array_key_exists($entityName, $this->config['entities'])) {
            return $this->render404error('@EasyAdmin/error/undefined_entity.html.twig', array('entity_name' => $entityName));
        }

        $this->entity = $this->get('easyadmin.configurator')->getEntityConfiguration($entityName);

        if (!$request->query->has('sortField')) {
            $request->query->set('sortField', $this->entity['primary_key_field_name']);
        }

        if (!$request->query->has('sortDirection') || !in_array(strtoupper($request->query->get('sortDirection')), array('ASC', 'DESC'))) {
            $request->query->set('sortDirection', 'DESC');
        }

        $this->em = $this->getDoctrine()->getManagerForClass($this->entity['class']);

        $this->request = $request;
    }

    protected function isActionAllowed($action, $view)
    {
        if ($action === $view || array_key_exists($action, $this->entity[$view]['actions'])) {
            return true;
        }

        return $this->render404error('@EasyAdmin/error/forbidden_action.html.twig', array(
            'action' => $action,
            'view'   => $view,
            'enabled_actions' => array_keys($this->entity[$view]['actions']),
        ));
    }

    /**
     * The method that is executed when the user performs a 'list' action on an entity.
     *
     * @return Response
     */
    protected function listAction()
    {
        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));

        return $this->render('@EasyAdmin/list.html.twig', array(
            'paginator' => $paginator,
            'fields'    => $fields,
            'view'      => 'list',
        ));
    }

    /**
     * The method that is executed when the user performs a 'edit' action on an entity.
     *
     * @return RedirectResponse|Response
     */
    protected function editAction()
    {
        if ($this->request->isXmlHttpRequest()) {
            return $this->ajaxEdit();
        }

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

            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'view' => 'list', 'entity' => $this->entity['name'])));
        }

        return $this->render('@EasyAdmin/edit.html.twig', array(
            'form'          => $editForm->createView(),
            'entity_fields' => $fields,
            'item'          => $item,
            'delete_form'   => $deleteForm->createView(),
            'view'          => 'edit',
        ));
    }

    /**
     * The method that is executed when the user performs a 'show' action on an entity.
     *
     * @return Response
     */
    protected function showAction()
    {
        if (!$item = $this->em->getRepository($this->entity['class'])->find($this->request->query->get('id'))) {
            throw $this->createNotFoundException(sprintf('Unable to find entity (%s #%d).', $this->entity['name'], $this->request->query->get('id')));
        }

        $fields = $this->entity['show']['fields'];
        $deleteForm = $this->createDeleteForm($this->entity['name'], $this->request->query->get('id'));

        return $this->render('@EasyAdmin/show.html.twig', array(
            'item'   => $item,
            'fields' => $fields,
            'view'   => 'show',
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * The method that is executed when the user performs a 'new' action on an entity.
     *
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

            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'view' => 'new', 'entity' => $this->entity['name'])));
        }

        return $this->render('@EasyAdmin/new.html.twig', array(
            'form'          => $newForm->createView(),
            'entity_fields' => $fields,
            'item'          => $item,
            'view'          => 'new',
        ));
    }

    /**
     * The method that is executed when the user performs a 'delete' action to
     * remove any entity.
     *
     * @return RedirectResponse
     */
    protected function deleteAction()
    {
        if ('DELETE' !== $this->request->getMethod()) {
            return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'view' => 'list', 'entity' => $this->entity['name'])));
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

        return $this->redirect($this->generateUrl('admin', array('action' => 'list', 'view' => 'list', 'entity' => $this->entity['name'])));
    }

    /**
     * The method that is executed when the user performs a query on an entity.
     *
     * @return Response
     */
    protected function searchAction()
    {
        $searchableFields = $this->entity['search']['fields'];
        $paginator = $this->findBy($this->entity['class'], $this->request->query->get('query'), $searchableFields, $this->request->query->get('page', 1), $this->config['list']['max_results']);
        $fields = $this->entity['list']['fields'];

        return $this->render('@EasyAdmin/list.html.twig', array(
            'paginator' => $paginator,
            'fields'    => $fields,
            'view'      => 'search',
        ));
    }

    /**
     * Modifies the entity properties via an Ajax call. Currently it's used for
     * changing the value of boolean properties when the user clicks on the
     * flip switched displayed for boolean values in the 'list' action.
     */
    protected function ajaxEdit()
    {
        if (!$entity = $this->em->getRepository($this->entity['class'])->find($this->request->query->get('id'))) {
            throw new \Exception('The entity does not exist.');
        }

        $propertyName = $this->request->query->get('property');
        $propertyMetadata = $this->entity['list']['fields'][$propertyName];

        if (!isset($this->entity['list']['fields'][$propertyName]) || 'toggle' != $propertyMetadata['dataType']) {
            throw new \Exception(sprintf('The "%s" property is not a switchable toggle.', $propertyName));
        }

        if (!$propertyMetadata['canBeSet']) {
            throw new \Exception(sprintf('It\'s not possible to toggle the value of the "%s" boolean property of the "%s" entity.', $propertyName, $this->entity['name']));
        }

        $newValue = ('true' === strtolower($this->request->query->get('newValue'))) ? true : false;
        if (null !== $setter = $propertyMetadata['setter']) {
            $entity->{$setter}($newValue);
        } else {
            $entity->{$propertyName} = $newValue;
        }

        $this->em->flush();

        return new Response((string) $newValue);
    }

    /**
     * Allows applications to modify the entity associated with the item being
     * edited before persisting it.
     *
     * @param object $entity
     *
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
     * @param object $entity
     *
     * @return object
     */
    protected function prepareNewEntityForPersist($entity)
    {
        return $entity;
    }

    /**
     * Performs a database query to get all the records related to the given
     * entity. It supports pagination and field sorting.
     *
     * @param string $entityClass
     * @param int    $page
     * @param int    $maxPerPage
     * @param string $sortField
     * @param string $sortDirection
     *
     * @return Pagerfanta The paginated query results
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
     * Performs a database query based on the search query provided by the user.
     * It supports pagination and field sorting.
     *
     * @param string $entityClass
     * @param string $searchQuery
     * @param array  $searchableFields
     * @param int    $page
     * @param int    $maxPerPage
     *
     * @return Pagerfanta The paginated query results
     */
    protected function findBy($entityClass, $searchQuery, array $searchableFields, $page = 1, $maxPerPage = 15)
    {
        $query = $this->em->createQueryBuilder()
            ->select('entity')
            ->from($entityClass, 'entity')
        ;

        foreach ($searchableFields as $name => $metadata) {
            $wildcards = $this->getDoctrine()->getConnection()->getDatabasePlatform()->getWildcards();
            $searchQuery = addcslashes($searchQuery, implode('', $wildcards));
            $query->orWhere("entity.$name LIKE :query")->setParameter('query', '%'.$searchQuery.'%');
        }

        $paginator = new Pagerfanta(new DoctrineORMAdapter($query, false));
        $paginator->setMaxPerPage($maxPerPage);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * Creates the form used to edit an entity.
     *
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

            if ('association' === $metadata['fieldType'] && in_array($metadata['associationType'], array(ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY))) {
                continue;
            }

            if ('collection' === $metadata['fieldType']) {
                $formFieldOptions = array('allow_add' => true, 'allow_delete' => true);

                if (version_compare(\Symfony\Component\HttpKernel\Kernel::VERSION, '2.5.0', '>=')) {
                    $formFieldOptions['delete_empty'] = true;
                }
            }

            $form->add($name, $metadata['fieldType'], $formFieldOptions);
        }

        return $form->getForm();
    }

    /**
     * Creates the form used to create an entity.
     *
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
     * It returns the name of the first entity configured in the backend. It's
     * mainly used to redirect the homepage of the backend to the listing of the
     * first configured entity.
     *
     * @return mixed
     */
    protected function getNameOfTheFirstConfiguredEntity()
    {
        $entityNames = array_keys($this->config['entities']);

        return $entityNames[0];
    }

    /**
     * Creates the form used to delete an entity. It must be a form because
     * the deletion of the entity are always performed with the 'DELETE' HTTP method,
     * which requires a form to work in the current browsers.
     *
     * @param string $entityName
     * @param int    $entityId
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
     * Utility shortcut to render a template as a 404 error page.
     *
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
