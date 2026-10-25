<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * @extends AbstractCrudController<Category>
 */
class ConfirmationSubmitButtonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield TextField::new('slug');
    }

    public function configureActions(Actions $actions): Actions
    {
        $confirmSave = Action::new('confirmSave', 'Save with confirmation')
            ->linkToCrudAction(Action::EDIT)
            ->renderAsButton()
            ->askConfirmation('Save this item?');

        $confirmSaveAgain = Action::new('confirmSaveAgain', 'Another save with confirmation')
            ->linkToCrudAction(Action::EDIT)
            ->renderAsButton()
            ->askConfirmation('Save this item again?');

        $confirmCreate = Action::new('confirmCreate', 'Create with confirmation')
            ->linkToCrudAction(Action::NEW)
            ->renderAsButton()
            ->askConfirmation('Create this item?');

        return $actions
            ->add(Crud::PAGE_EDIT, $confirmSave)
            ->add(Crud::PAGE_EDIT, $confirmSaveAgain)
            ->add(Crud::PAGE_NEW, $confirmCreate);
    }
}
