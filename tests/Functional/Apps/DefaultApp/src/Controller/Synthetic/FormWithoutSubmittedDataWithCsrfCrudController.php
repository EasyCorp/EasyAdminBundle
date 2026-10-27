<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

/**
 * The same form as its parent, but with the default CSRF protection enabled.
 */
class FormWithoutSubmittedDataWithCsrfCrudController extends FormWithoutSubmittedDataCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud;
    }
}
