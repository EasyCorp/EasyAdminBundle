<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Admin;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class CustomCategoryControllerAsService
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * This controller doesn't extend from the default AdminController, so it's
     * mandatory to define the 'indexAction()' method too.
     */
    public function indexAction()
    {
        $actionName = $this->container->get('request_stack')->getMasterRequest()->query->get('action', 'list');
        $actionMethod = is_callable([$this, $actionName.'CategoryAction']) ? $actionName.'CategoryAction' : $actionName.'Action';

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
