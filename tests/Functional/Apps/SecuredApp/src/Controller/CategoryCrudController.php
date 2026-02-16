<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * CategoryCrudController for security tests.
 * Tests CSRF protection and role-based permissions.
 *
 * @extends AbstractCrudController<Category>
 */
class CategoryCrudController extends AbstractCrudController
{
    public const ACTION_ADMIN_ONLY = 'adminOnly';

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextField::new('slug'),
            BooleanField::new('active'),
            BooleanField::new('activeWithNoPermission')->setPermission('ROLE_SUPER_ADMIN'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $adminOnlyAction = Action::new(self::ACTION_ADMIN_ONLY)
            ->createAsGlobalAction()
            ->linkToCrudAction(self::ACTION_ADMIN_ONLY);

        return $actions
            ->add(Crud::PAGE_INDEX, $adminOnlyAction)
            ->setPermission(self::ACTION_ADMIN_ONLY, 'ROLE_ADMIN');
    }

    #[AdminRoute]
    public function adminOnly(AdminContext $context): Response
    {
        // check role directly for global actions (setPermission configures ROLE_ADMIN for this action)
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new ForbiddenActionException($context);
        }

        return new Response('Admin only page content');
    }
}
