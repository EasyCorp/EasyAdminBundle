<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\RestrictedDashboard;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * Used by RestrictedDashboardControllersTest: it's not available in GuestDashboardController
 * and it's registered after GuestUserCrudController, so it's the CRUD controller that
 * EasyAdmin finds for the User entity when the current dashboard is not considered.
 *
 * @extends AbstractCrudController<User>
 */
class StaffUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            EmailField::new('email'),
        ];
    }
}
