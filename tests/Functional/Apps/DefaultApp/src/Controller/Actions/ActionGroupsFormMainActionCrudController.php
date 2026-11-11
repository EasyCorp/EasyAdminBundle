<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * Tests ActionGroup with a main action rendered as a form.
 *
 * @extends AbstractCrudController<Category>
 */
class ActionGroupsFormMainActionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $statusGroup = ActionGroup::new('statusGroup', 'Status')
            ->addMainAction(Action::new('publish', 'Publish')->linkToCrudAction('detail')->renderAsForm())
            ->addAction(Action::new('archive', 'Archive')->linkToCrudAction('detail'));
        $globalStatusGroup = ActionGroup::new('globalStatusGroup', 'Status')
            ->createAsGlobalActionGroup()
            ->addMainAction(Action::new('publishAll', 'Publish All')->linkToCrudAction('index')->renderAsForm())
            ->addAction(Action::new('archiveAll', 'Archive All')->linkToCrudAction('index'));

        return $actions
            ->add(Crud::PAGE_INDEX, $globalStatusGroup)
            ->add(Crud::PAGE_INDEX, $statusGroup)
            ->add(Crud::PAGE_DETAIL, $statusGroup)
            ->add(Crud::PAGE_EDIT, $statusGroup);
    }
}
