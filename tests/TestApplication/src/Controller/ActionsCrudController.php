<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;

/**
 * Tests the configureActions() method and the generated actions.
 */
class ActionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $action1 = Action::new('action1')->linkToCrudAction('');
        $action2 = Action::new('action2')->linkToCrudAction('')->setCssClass('foo');
        $action3 = Action::new('action3')->linkToCrudAction('')->addCssClass('bar');
        $action4 = Action::new('action4')->linkToCrudAction('')->setCssClass('foo')->addCssClass('bar');

        return $actions
            ->add(Crud::PAGE_INDEX, $action1)
            ->add(Crud::PAGE_INDEX, $action2)
            ->add(Crud::PAGE_INDEX, $action3)
            ->add(Crud::PAGE_INDEX, $action4)
        ;
    }
}
