<?php

namespace App\Controller\Admin;

use App\Entity\PurchaseItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class PurchaseItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PurchaseItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'quantity', 'taxRate']);
    }

    public function configureFields(string $pageName): iterable
    {
        $quantity = IntegerField::new('quantity');
        $taxRate = NumberField::new('taxRate');
        $product = AssociationField::new('product');
        $purchase = AssociationField::new('purchase');
        $id = IntegerField::new('id', 'ID');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $quantity, $taxRate, $product, $purchase];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $quantity, $taxRate, $product, $purchase];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$quantity, $taxRate, $product, $purchase];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$quantity, $taxRate, $product, $purchase];
        }
    }
}
