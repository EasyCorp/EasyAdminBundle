<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\LayoutPermission;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * The tab with the boolean switch requires ROLE_ADMIN, but the switch doesn't define any permission.
 *
 * @extends AbstractCrudController<Category>
 */
class RestrictedTabCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
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
            FormField::addTab('General'),
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),

            FormField::addTab('Status')->setPermission('ROLE_ADMIN'),
            BooleanField::new('active'),
        ];
    }
}
