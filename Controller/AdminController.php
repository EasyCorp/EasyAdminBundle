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

    protected $view;

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

        // for now, the homepage redirects to the 'list' action and view of the first entity
        if (null === $request->query->get('entity')) {
            return $this->redirect($this->generateUrl('admin', array(
                'action' => $action,
                'entity' => $this->getNameOfTheFirstConfiguredEntity(),
                'view'   => $this->view,
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
        $this->view = $this->request->query->get('view', 'list');
    }

    /**
     * The method that is executed when the user performs a 'list' action on an entity.
     *
     * @return Response
     */
    protected function listAction()
    {
        if (!$this->isActionAllowed('list')) {
            return $this->renderForbiddenActionError('list');
        }

        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->config['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'));

        return $this->render($this->entity['templates']['list'], array(
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
        if (!$this->isActionAllowed('edit')) {
            return $this->renderForbiddenActionError('edit');
        }

        if ($this->request->isXmlHttpRequest()) {
            return $this->ajaxEdit();
        }

        $id = $this->request->query->get('id');
        if (!$item = $this->em->getRepository($this->entity['class'])->find($id)) {
            throw $this->createNotFoundException(sprintf('Unable to find entity (%s #%d).', $this->entity['name'], $id));
        }

        $fields = $this->entity['edit']['fields'];
        $editForm = $this->createEditForm($item, $fields);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $editForm->handleRequest($this->request);
        if ($editForm->isValid()) {
            $this->prepareEditEntityForPersist($item);
            $this->em->flush();

            return !empty($refererUrl = $this->request->query->get('referer', ''))
                ? $this->redirect(urldecode($refererUrl))
                : $this->redirect($this->generateUrl('admin', array('action' => 'list', 'view' => 'list', 'entity' => $this->entity['name'])));
        }

        return $this->render($this->entity['templates']['edit'], array(
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
        if (!$this->isActionAllowed('show')) {
            return $this->renderForbiddenActionError('show');
        }

        $id = $this->request->query->get('id');
        if (!$item = $this->em->getRepository($this->entity['class'])->find($id)) {
            throw $this->createNotFoundException(sprintf('Unable to find entity (%s #%d).', $this->entity['name'], $id));
        }

        $fields = $this->entity['show']['fields'];
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        return $this->render($this->entity['templates']['show'], array(
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
        if (!$this->isActionAllowed('new')) {
            return $this->renderForbiddenActionError('new');
        }

        $item = $this->instantiateNewEntity();

        $fields = $fields = $this->entity['new']['fields'];
        $newForm = $this->createNewForm($item, $fields);

        $newForm->handleRequest($this->request);
        if ($newForm->isValid()) {
            $this->prepareNewEntityForPersist($item);
            $this->em->persist($item);
            $this->em->flush();

            return !empty($refererUrl = $this->request->query->get('referer', ''))
                ? $this->redirect(urldecode($refererUrl))
                : $this->redirect($this->generateUrl('admin', array('action' => 'list', 'view' => 'new', 'entity' => $this->entity['name'])));
        }

        return $this->render($this->entity['templates']['new'], array(
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

        $id = $this->request->query->get('id');
        $form = $this->createDeleteForm($this->entity['name'], $id);
        $form->handleRequest($this->request);

        if ($form->isValid()) {
            if (!$entity = $this->em->getRepository($this->entity['class'])->find($id)) {
                throw $this->createNotFoundException('The entity to be deleted does not exist.');
            }

            $this->em->remove($entity);
            $this->em->flush();
        }

        return !empty($refererUrl = $this->request->query->get('referer', ''))
            ? $this->redirect(urldecode($refererUrl))
            : $this->redirect($this->generateUrl('admin', array('action' => 'list', 'view' => 'list', 'entity' => $this->entity['name'])));
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

        return $this->render($this->entity['templates']['list'], array(
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
     * Creates a new object of the current managed entity.
     * This method is mostly here for override convenience, because it allows
     * the user to use his own method to customize the entity instanciation.
     *
     * @return object
     */
    protected function instantiateNewEntity()
    {
        $entityFullyQualifiedClassName = $this->entity['class'];

        return new $entityFullyQualifiedClassName();
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
     * @param string      $entityClass
     * @param int         $page
     * @param int         $maxPerPage
     * @param string|null $sortField
     * @param string|null $sortDirection
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
            if (empty($sortDirection) || !in_array(strtoupper($sortDirection), array('ASC', 'DESC'))) {
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
        $queryBuilder = $this->em->createQueryBuilder()->select('entity')->from($entityClass, 'entity');

        $queryConditions = $queryBuilder->expr()->orX();
        $queryParameters = array();
        foreach ($searchableFields as $name => $metadata) {
            if (in_array($metadata['dataType'], array('text', 'string'))) {
                $queryConditions->add(sprintf('entity.%s LIKE :query', $name));
                $queryParameters['query'] = '%'.$searchQuery.'%';
            } else {
                $queryConditions->add(sprintf('entity.%s IN (:words)', $name));
                $queryParameters['words'] = explode(' ', $searchQuery);
            }
        }

        $queryBuilder->add('where', $queryConditions)->setParameters($queryParameters);

        $paginator = new Pagerfanta(new DoctrineORMAdapter($queryBuilder, false));
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
        return $this->createEntityForm($entity, $entityProperties, 'edit');
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
        return $this->createEntityForm($entity, $entityProperties, 'new');
    }

    /**
     * Creates the form used to create or edit an entity.
     *
     * @param object $entity
     * @param array  $entityProperties
     * @param string $view             The name of the view where this form is used ('new' or 'edit')
     *
     * @return Form
     */
    protected function createEntityForm($entity, array $entityProperties, $view)
    {
        $formCssClass = array_reduce($this->config['design']['form_theme'], function ($previousClass, $formTheme) {
            return sprintf('theme_%s %s', strtolower(str_replace('.html.twig', '', basename($formTheme))), $previousClass);
        });

        $form = $this->createFormBuilder($entity, array(
            'data_class' => $this->entity['class'],
            'attr' => array('class' => $formCssClass, 'id' => $view.'-form'),
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

            $formFieldOptions['attr']['field_type'] = $metadata['fieldType'];
            $formFieldOptions['attr']['field_css_class'] = $metadata['class'];
            $formFieldOptions['attr']['field_help'] = $metadata['help'];

            $form->add($name, $metadata['fieldType'], $formFieldOptions);
        }

        return $form->getForm();
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

    /**
     * Utility method that checks if the given action is allowed for the current
     * view of the current entity.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function isActionAllowed($action)
    {
        return array_key_exists($action, $this->entity[$this->view]['actions']);
    }

    /**
     * Utility shortcut to render an error when the requested action is not allowed
     * for the given view of the given entity.
     *
     * @param string $action
     *
     * @return Response
     */
    protected function renderForbiddenActionError($action)
    {
        $allowedActions = array_keys($this->entity[$this->view]['actions']);
        $parameters = array('action' => $action, 'allowed_actions' => $allowedActions, 'view' => $this->view);

        return $this->render('@EasyAdmin/error/forbidden_action.html.twig', $parameters, new Response('', 403));
    }

    /**
     * It renders the main CSS applied to the backend design. This controller
     * allows to generate dynamic CSS files that use variables without the need
     * to set up a CSS preprocessing toolchain.
     *
     * @Route("/_css/admin.css", name="_easyadmin_render_css")
     */
    public function renderCssAction()
    {
        $config = $this->container->getParameter('easyadmin.config');

        $cssContent = $this->renderView('@EasyAdmin/css/admin.css.twig', array(
            'brand_color' => $config['design']['brand_color'],
            'color_scheme' => $config['design']['color_scheme'],
        ));

        $response = new Response($cssContent, 200, array('Content-Type' => 'text/css'));
        $response->setPublic();
        $response->setSharedMaxAge(600);

        return $response;
    }
}
