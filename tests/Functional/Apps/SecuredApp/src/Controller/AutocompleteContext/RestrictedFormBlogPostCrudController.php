<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\AutocompleteContext;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security\UnpublishedBlogPostVoter;

/**
 * Used by AutocompleteContextTest: the NEW page requires a role, the EDIT page requires a
 * permission that depends on the entity and the "publisher" field requires a role.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class RestrictedFormBlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, UnpublishedBlogPostVoter::UNPUBLISHED_BLOG_POST);
    }

    public function configureFields(string $pageName): iterable
    {
        $formatUser = static fn (User $user): string => sprintf('%s (%s)', $user->getName(), $user->getEmail());

        return [
            TextField::new('title'),
            AssociationField::new('author')
                ->setCrudController(AutocompleteUserCrudController::class)
                ->autocomplete(callback: $formatUser),
            AssociationField::new('publisher')
                ->setCrudController(AutocompleteUserCrudController::class)
                ->setPermission('ROLE_ADMIN')
                ->autocomplete(callback: $formatUser),
        ];
    }
}
