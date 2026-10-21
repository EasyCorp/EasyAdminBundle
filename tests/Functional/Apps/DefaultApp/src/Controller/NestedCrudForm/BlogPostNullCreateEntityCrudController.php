<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * Embedded CRUD controller whose createEntity() returns null, so the collection
 * entries are created by the "empty_data" option of the entry form.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostNullCreateEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Blog Post Title');
        yield TextField::new('slug');
        yield TextField::new('content');
        yield DateTimeField::new('createdAt');
    }

    public function createEntity(string $entityFqcn): ?object
    {
        return null;
    }
}
