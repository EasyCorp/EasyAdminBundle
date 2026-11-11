<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

/**
 * Renders the same action groups inlined in each row of the index page (not as a dropdown).
 */
class ActionGroupsMainActionInlineCrudController extends ActionGroupsMainActionCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined();
    }
}
