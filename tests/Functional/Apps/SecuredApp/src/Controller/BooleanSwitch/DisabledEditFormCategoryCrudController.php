<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Everybody can open the edit page, but its form is disabled for the users that are not admins.
 *
 * @extends AbstractCrudController<Category>
 */
class DisabledEditFormCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setFormOptions([], ['disabled' => !$this->isGranted('ROLE_ADMIN')]);
    }

    /**
     * Prints the CSRF token of the switch, which is not printed in the index page of the users that are not admins.
     */
    #[AdminRoute]
    public function activeToggleCsrfToken(): Response
    {
        return new Response($this->container->get('security.csrf.token_manager')->getToken(BooleanField::CSRF_TOKEN_NAME.'-'.self::class.'-active')->getValue());
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            BooleanField::new('active'),
        ];
    }
}
