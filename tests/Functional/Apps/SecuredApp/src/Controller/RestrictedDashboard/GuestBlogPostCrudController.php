<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\RestrictedDashboard;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * Used by RestrictedDashboardControllersTest: the "author" association doesn't
 * define the CRUD controller of its embedded form, so EasyAdmin has to find it.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class GuestBlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            AssociationField::new('author')->renderAsEmbeddedForm(),
        ];
    }
}
