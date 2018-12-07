<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Symfony\Component\HttpFoundation\Response;

class CustomCategoryController extends EasyAdminController
{
    public function listAction()
    {
        return new Response('Overridden list action.');
    }

    /**
     * It's absurd to use the entity name in the action method because we are
     * already using a custom controller for the entity. But this should be
     * possible for consistency and this test makes sure it's working.
     */
    public function showCategoryAction()
    {
        return new Response('Overridden show action.');
    }
}
