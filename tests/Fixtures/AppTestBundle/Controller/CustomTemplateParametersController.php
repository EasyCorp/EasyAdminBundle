<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;

class CustomTemplateParametersController extends EasyAdminController
{
    public function listAction()
    {
        $this->templateParameters = array(
            'custom_parameter' => 'list',
        );

        return parent::listAction();
    }

    public function showAction()
    {
        $this->templateParameters = array(
            'custom_parameter' => 'show',
        );

        return parent::showAction();
    }

    public function searchAction()
    {
        $this->templateParameters = array(
            'custom_parameter' => 'search',
        );

        return parent::searchAction();
    }

    public function editAction()
    {
        $this->templateParameters = array(
            'custom_parameter' => 'edit',
        );

        return parent::editAction();
    }

    public function newAction()
    {
        $this->templateParameters = array(
            'custom_parameter' => 'new',
        );

        return parent::newAction();
    }
}
