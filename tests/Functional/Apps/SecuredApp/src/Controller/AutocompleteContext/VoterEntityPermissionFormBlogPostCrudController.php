<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ProtectedUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security\UnpublishedBlogPostVoter;

/**
 * Used by AutocompleteContextTest: its pages are only protected by an entity permission
 * that needs the entity to decide, so its NEW page is denied to everybody.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class VoterEntityPermissionFormBlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityPermission(UnpublishedBlogPostVoter::UNPUBLISHED_BLOG_POST);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            AssociationField::new('author')
                ->setCrudController(ProtectedUserCrudController::class)
                ->hideOnIndex()
                ->autocomplete(callback: static fn (User $user): string => sprintf('%s (%s)', $user->getName(), $user->getEmail())),
        ];
    }
}
