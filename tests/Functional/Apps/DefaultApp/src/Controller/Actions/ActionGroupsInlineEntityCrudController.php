<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

/**
 * Tests ActionGroup with inline entity actions (not as dropdown).
 */
class ActionGroupsInlineEntityCrudController extends ActionGroupsEntityCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined();
    }
}
