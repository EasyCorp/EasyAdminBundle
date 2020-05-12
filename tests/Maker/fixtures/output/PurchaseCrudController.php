<?php

namespace App\Controller\Admin;

use App\Entity\Purchase;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

class PurchaseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Purchase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Purchase')
            ->setEntityLabelInPlural('Purchase')
            ->setSearchFields(['id', 'guid', 'billingAddress']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('delete');
    }

    public function configureFields(string $pageName): iterable
    {
        $panel1 = FormField::addPanel('Delivery Details');
        $deliveryDate = DateField::new('deliveryDate');
        $deliveryHour = TimeField::new('deliveryHour');
        $billingAddress = TextareaField::new('billingAddress');
        $panel2 = FormField::addPanel('Purchase Details');
        $guid = TextField::new('guid');
        $buyer = AssociationField::new('buyer');
        $id = TextField::new('id', 'ID');
        $createdAt = DateTimeField::new('createdAt');
        $shipping = TextField::new('shipping');
        $purchasedItems = AssociationField::new('purchasedItems');
        $total = TextareaField::new('total');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$guid, $buyer, $deliveryDate, $deliveryHour, $billingAddress, $purchasedItems, $total];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $guid, $deliveryDate, $createdAt, $shipping, $deliveryHour, $billingAddress, $buyer, $purchasedItems];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$panel1, $deliveryDate, $deliveryHour, $billingAddress, $panel2, $guid, $buyer];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$panel1, $deliveryDate, $deliveryHour, $billingAddress, $panel2, $guid, $buyer];
        }
    }
}
