<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * The restricted form is embedded in another embedded form.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithAuthorAndRestrictedFavoriteCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield AssociationField::new('author')->renderAsEmbeddedForm(UserWithRestrictedFavoriteCategoryCrudController::class);
    }
}
