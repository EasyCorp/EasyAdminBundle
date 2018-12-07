<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;

class CustomTemplateParametersController extends EasyAdminController
{
    protected function renderTemplate($actionName, $templatePath, array $parameters = [])
    {
        $parameters['custom_parameter'] = $actionName;

        return parent::renderTemplate($actionName, $templatePath, $parameters);
    }
}
