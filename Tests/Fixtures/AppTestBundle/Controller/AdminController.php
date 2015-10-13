<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;

/**
 * Class AdminController
 * Many methods are only here to make the method public
 *   so they can be unit tested
 */
class AdminController extends EasyAdminController
{

    public $entity;

    public function initialize(Request $request)
    {
        parent::initialize($request);
    }

    public function createEntityFormBuilder($entity, array $entityProperties, $view)
    {
        return parent::createEntityFormBuilder($entity, $entityProperties, $view);
    }

    public function findCurrentEntity()
    {
        return parent::findCurrentEntity();
    }
}
