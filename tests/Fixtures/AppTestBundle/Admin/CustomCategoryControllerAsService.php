<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Admin;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CustomCategoryControllerAsService
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * This controller doesn't extend from the default AdminController, so it's
     * mandatory to define the 'indexAction()' method too.
     */
    public function indexAction()
    {
        $actionName = $this->requestStack->getMasterRequest()->query->get('action', 'list');
        $actionMethod = \is_callable([$this, $actionName.'CategoryAction']) ? $actionName.'CategoryAction' : $actionName.'Action';

        return $this->{$actionMethod}();
    }

    public function listAction()
    {
        return new Response('Overridden list action as a service.');
    }

    /**
     * It's absurd to use the entity name in the action method because we are
     * already using a custom controller for the entity. But this should be
     * possible for consistency and this test makes sure it's working.
     */
    public function showCategoryAction()
    {
        return new Response('Overridden show action as a service.');
    }
}
