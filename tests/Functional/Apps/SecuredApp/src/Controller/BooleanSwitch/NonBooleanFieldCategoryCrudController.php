<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\BooleanSwitch;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prints the CSRF token that a boolean switch of the name property would use, which no switch prints.
 *
 * @extends AbstractCrudController<Category>
 */
class NonBooleanFieldCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    #[AdminRoute]
    public function nameToggleCsrfToken(): Response
    {
        return new Response($this->container->get('security.csrf.token_manager')->getToken(BooleanField::CSRF_TOKEN_NAME.'-'.self::class.'-name')->getValue());
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
