<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\NestedAssociationSortTestEntity;

/**
 * Exposes a nested AssociationField (category.parent) sortable by the leaf category's
 * `name`, so the index can be ordered via ?sort[category.parent]. No setCrudController()
 * is set: the configurator must auto-resolve the leaf entity's CRUD controller to render
 * the cell as a link.
 *
 * @extends AbstractCrudController<NestedAssociationSortTestEntity>
 */
class NestedAssociationSortTestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return NestedAssociationSortTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');

        yield AssociationField::new('category.parent')
            ->setSortProperty('name');
    }
}
