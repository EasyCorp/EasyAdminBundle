<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'tags', 'ean', 'image', 'features', 'price', 'name', 'description']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('enabled', 'Foo Bar'))
            ->add('name')
            ->add('price');
    }

    public function configureFields(string $pageName): iterable
    {
        $panel1 = FormField::addPanel('Basic information');
        $name = TextField::new('name', 'áéíóúäëïöüñ[]%@# Name');
        $description = TextareaField::new('description');
        $categories = AssociationField::new('categories');
        $panel2 = FormField::addPanel('Product Details');
        $ean = TextField::new('ean');
        $price = NumberField::new('price')->addCssClass('text-right');
        $enabled = Field::new('enabled');
        $createdAt = DateTimeField::new('createdAt');
        $panel3 = FormField::addPanel();
        $features = ArrayField::new('features');
        $panel4 = FormField::addPanel();
        $tags = ArrayField::new('tags');
        $panel5 = FormField::addPanel('Attachments');
        $imageFile = Field::new('imageFile');
        $id = IntegerField::new('id', 'ID');
        $image = ImageField::new('image');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $enabled, $name, $price, $ean, $image, $createdAt, $tags];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $description, $enabled, $createdAt, $price, $ean, $image, $features, $categories, $tags];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$panel1, $name, $description, $categories, $panel2, $ean, $price, $enabled, $createdAt, $panel3, $features, $panel4, $tags, $panel5, $imageFile];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$panel1, $name, $description, $categories, $panel2, $ean, $price, $enabled, $createdAt, $panel3, $features, $panel4, $tags, $panel5, $imageFile];
        }
    }
}
