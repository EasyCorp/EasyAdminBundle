<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * CRUD controller for testing an AssociationField whose property is a nested
 * association path ending at an association ('publisher.favoriteCategory'). The field
 * defines setSortProperty('name'), so sorting orders by the related category's name.
 * It doesn't call setCrudController(): the cell must auto-link to the CRUD controller
 * of the entity at the end of the path (Category).
 *
 * @extends AbstractCrudController<BlogPost>
 */
class SortNestedAssociationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort(['title' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield AssociationField::new('publisher.favoriteCategory')
            ->setSortProperty('name');
    }
}
