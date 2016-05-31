<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Admin;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;
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
