<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('User')
            ->setPageTitle(Crud::PAGE_INDEX, 'Customers')
            ->setSearchFields(['id', 'username', 'email', 'contract']);
    }

    public function configureFields(string $pageName): iterable
    {
        $panel1 = FormField::addPanel('Account Information');
        $username = TextField::new('username');
        $isActive = TextareaField::new('isActive');
        $panel2 = FormField::addPanel('Contact Information');
        $email = TextField::new('email');
        $panel3 = FormField::addPanel('Legal Information');
        $contractFile = Field::new('contractFile');
        $panel4 = FormField::addPanel('Transactions History');
        $purchases = AssociationField::new('purchases');
        $id = IntegerField::new('id', 'ID');
        $contract = TextField::new('contract')->setTemplatePath('easy_admin/User/contract.html.twig');
        $active = Field::new('active');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $active, $username, $email, $purchases, $contract];
        } elseif (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $isActive, $username, $email, $purchases, $contract];
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$panel1, $username, $isActive, $panel2, $email, $panel3, $contractFile, $panel4, $purchases];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$panel1, $username, $isActive, $panel2, $email, $panel3, $contractFile, $panel4, $purchases];
        }
    }
}
