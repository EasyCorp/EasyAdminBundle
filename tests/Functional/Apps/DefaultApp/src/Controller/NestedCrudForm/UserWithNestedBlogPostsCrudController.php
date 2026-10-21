<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * CRUD controller for User that uses CollectionField with useEntryCrudForm().
 * This is used to test that nested CRUD forms work correctly with the AdminContext fix.
 *
 * @extends AbstractCrudController<User>
 */
class UserWithNestedBlogPostsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'User Name');

        // BlogPostNestedCrudController accesses $context->getEntity() in its
        // configureFields(), which tests the bug fix
        yield CollectionField::new('blogPosts', 'Blog Posts')
            ->renderExpanded(true)
            ->useEntryCrudForm(BlogPostNestedCrudController::class);
    }
}
