<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\ProtectedUserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security\UnpublishedBlogPostVoter;

/**
 * Used by AutocompleteContextTest: the "publisher" field doesn't define any permission,
 * but it's inside a tab that requires a role.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class RestrictedTabBlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->setPermission(Action::EDIT, UnpublishedBlogPostVoter::UNPUBLISHED_BLOG_POST);
    }

    public function configureFields(string $pageName): iterable
    {
        $formatUser = static fn (User $user): string => sprintf('%s (%s)', $user->getName(), $user->getEmail());

        return [
            FormField::addTab('Content'),
            TextField::new('title'),
            AssociationField::new('author')
                ->setCrudController(ProtectedUserCrudController::class)
                ->autocomplete(callback: $formatUser),

            FormField::addTab('Publishing')->setPermission('ROLE_ADMIN'),
            AssociationField::new('publisher')
                ->setCrudController(ProtectedUserCrudController::class)
                ->autocomplete(callback: $formatUser),
        ];
    }
}
