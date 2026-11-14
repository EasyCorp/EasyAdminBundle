<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * Used by PlaceholderEscapingTest to check the default page titles when the
 * entity label is built by a closure that escapes the entity values.
 *
 * @extends AbstractCrudController<User>
 */
class PlaceholderEntityLabelUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular(
            static fn (?User $user): string => null === $user ? 'User' : sprintf('User <i>%s</i>', htmlspecialchars((string) $user->getName()))
        );
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('email'),
        ];
    }
}
