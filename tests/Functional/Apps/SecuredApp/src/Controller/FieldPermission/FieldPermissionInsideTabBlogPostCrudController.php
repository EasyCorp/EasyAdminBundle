<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller\FieldPermission;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * A fieldset and a tab require ROLE_ADMIN, but the fields inside them don't define any
 * permission. Keeps the default search options, which search in all the mapped properties.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class FieldPermissionInsideTabBlogPostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Content'),
            IdField::new('id')->hideOnForm(),
            TextField::new('title'),
            FormField::addFieldset('Body')->setPermission('ROLE_ADMIN'),
            TextareaField::new('content'),

            FormField::addTab('Publishing')->setPermission('ROLE_ADMIN'),
            TextField::new('slug'),
            AssociationField::new('author'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('content')
            ->add('slug');
    }
}
