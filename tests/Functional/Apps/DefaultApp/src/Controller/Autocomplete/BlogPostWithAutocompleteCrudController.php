<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Autocomplete;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithAutocompleteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->autocomplete(
                callback: static fn (BlogPost $blogPost): string => sprintf('[%s] %s', $blogPost->getId() ?? 'NEW', $blogPost->getTitle())
            );
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            // the field-level callback overrides the CRUD-level one
            AssociationField::new('categories')->autocomplete(
                callback: static fn (Category $category): string => sprintf('Category: %s (ID: %s)', $category->getName(), $category->getId())
            ),
        ];
    }
}
