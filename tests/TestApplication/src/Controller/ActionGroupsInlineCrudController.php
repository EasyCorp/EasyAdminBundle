<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

/**
 * Tests ActionGroup with inline entity actions (not as dropdown).
 */
class ActionGroupsInlineCrudController extends ActionGroupsCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined();
    }
}
