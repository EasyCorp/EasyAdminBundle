<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\EmbeddedForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * CRUD controller for BlogPost that embeds the forms of two different CRUD
 * controllers of User, both selected with setCrudController().
 *
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithEmbeddedUsersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');

        // User has many CRUD controllers and only one of them is found automatically,
        // so at least one of these fields depends on the controller set explicitly
        yield AssociationField::new('author')
            ->setCrudController(UserNameEmbeddedCrudController::class)
            ->renderAsEmbeddedForm();
        yield AssociationField::new('publisher')
            ->setCrudController(UserEmailEmbeddedCrudController::class)
            ->renderAsEmbeddedForm();
    }
}
