<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Product %entity_label_singular%')
            ->setSearchFields(['id', 'name']);
    }

    public function configureFields(string $pageName): iterable
    {
        $panel1 = FormField::addPanel();
        $name = TextField::new('name');
        $parent = AssociationField::new('parent');
        $id = IntegerField::new('id', 'ID');
        $products = AssociationField::new('products');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $parent, $products];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $parent, $products];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$panel1, $name, $parent];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$panel1, $name, $parent];
        }
    }
}
