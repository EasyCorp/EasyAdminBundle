<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UrlSortSecurityTestEntity;

/**
 * @extends AbstractCrudController<UrlSortSecurityTestEntity>
 */
class UrlSortSecurityTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UrlSortSecurityTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        // `hiddenField` and `otherField` are Doctrine-mapped on the entity but
        // intentionally not exposed here, so ?sort[hiddenField] / ?sort[otherField]
        // requests must be rejected by EntityRepository::addOrderClause
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('displayedField');
    }
}
