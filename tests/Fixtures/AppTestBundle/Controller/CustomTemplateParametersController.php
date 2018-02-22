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
    protected function renderTemplate($actionName, $templatePath, array $parameters = array())
    {
        $parameters['custom_parameter'] = $actionName;

        return parent::renderTemplate($actionName, $templatePath, $parameters);
    }
}
