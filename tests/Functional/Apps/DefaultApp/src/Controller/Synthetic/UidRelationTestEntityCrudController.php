<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UidRelationTestEntity;

/**
 * Tests the autocomplete fields and the entity filters of associations to entities whose
 * id is a Uuid or a Ulid.
 *
 * @extends AbstractCrudController<UidRelationTestEntity>
 */
class UidRelationTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UidRelationTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name');
        yield AssociationField::new('uuidRelation')->autocomplete();
        yield AssociationField::new('ulidRelation')->autocomplete();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('uuidRelation'))
            ->add(EntityFilter::new('ulidRelation'));
    }
}
