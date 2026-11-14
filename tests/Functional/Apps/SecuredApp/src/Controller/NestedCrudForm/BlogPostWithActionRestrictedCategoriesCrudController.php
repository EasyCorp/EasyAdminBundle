<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * Edits the categories with the form of a controller that only restricts its actions.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithActionRestrictedCategoriesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield CollectionField::new('categories')->useEntryCrudForm(ActionRestrictedCategoryCrudController::class);
    }
}
