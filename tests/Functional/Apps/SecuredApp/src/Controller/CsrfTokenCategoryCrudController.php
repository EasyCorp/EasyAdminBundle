<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Used by CsrfProtectionTest to check that the CSRF tokens of the boolean
 * switches and of the delete action are only valid for the CRUD controller
 * (and the field) they were generated for.
 *
 * @extends AbstractCrudController<Category>
 */
class CsrfTokenCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            BooleanField::new('active'),
            BooleanField::new('activeDisabled'),
        ];
    }

    /**
     * Generates tokens the same way as applications that still print
     * the token ids used before they were bound to the CRUD controller.
     */
    #[AdminRoute]
    public function legacyCsrfTokens(): JsonResponse
    {
        $csrfTokenManager = $this->container->get('security.csrf.token_manager');

        return new JsonResponse([
            'delete' => $csrfTokenManager->getToken('ea-delete')->getValue(),
            'toggle' => $csrfTokenManager->getToken(BooleanField::CSRF_TOKEN_NAME)->getValue(),
        ]);
    }
}
