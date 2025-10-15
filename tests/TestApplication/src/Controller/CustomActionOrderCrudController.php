<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;

/**
 * Tests custom action ordering with smart sorting enabled.
 *
 * @extends AbstractCrudController<Category>
 */
class CustomActionOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        // create custom actions with different variants and styles to test ordering
        $warningAction = Action::new('warning_action', 'Warning Action')
            ->linkToCrudAction('index')
            ->asWarningAction();

        $primaryAction = Action::new('primary_action', 'Primary Action')
            ->linkToCrudAction('index')
            ->asPrimaryAction();

        $dangerTextAction = Action::new('danger_text_action', 'Danger Text')
            ->linkToCrudAction('index')
            ->asDangerAction()
            ->asTextLink();

        $successAction = Action::new('success_action', 'Success Action')
            ->linkToCrudAction('index')
            ->asSuccessAction();

        $textAction = Action::new('text_action', 'Text Action')
            ->linkToCrudAction('index')
            ->asTextLink();

        $dangerSolidAction = Action::new('danger_solid_action', 'Danger Solid')
            ->linkToCrudAction('index')
            ->asDangerAction();

        $defaultAction = Action::new('default_action', 'Default Action')
            ->linkToCrudAction('index');

        // add custom global actions for index page
        $globalPrimary = Action::new('global_primary', 'Global Primary')
            ->linkToCrudAction('index')
            ->createAsGlobalAction()
            ->asPrimaryAction();

        $globalSuccess = Action::new('global_success', 'Global Success')
            ->linkToCrudAction('index')
            ->createAsGlobalAction()
            ->asSuccessAction();

        $globalWarning = Action::new('global_warning', 'Global Warning')
            ->linkToCrudAction('index')
            ->createAsGlobalAction()
            ->asWarningAction();

        $globalDanger = Action::new('global_danger', 'Global Danger')
            ->linkToCrudAction('index')
            ->createAsGlobalAction()
            ->asDangerAction();

        $globalText = Action::new('global_text', 'Global Text')
            ->linkToCrudAction('index')
            ->createAsGlobalAction()
            ->asTextLink();

        return $actions
            // add entity actions in mixed order for index page
            ->add(Crud::PAGE_INDEX, $warningAction)
            ->add(Crud::PAGE_INDEX, $primaryAction)
            ->add(Crud::PAGE_INDEX, $dangerTextAction)
            ->add(Crud::PAGE_INDEX, $successAction)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $textAction)
            ->add(Crud::PAGE_INDEX, $dangerSolidAction)
            ->add(Crud::PAGE_INDEX, $defaultAction)

            // add global actions in mixed order
            ->add(Crud::PAGE_INDEX, $globalWarning)
            ->add(Crud::PAGE_INDEX, $globalPrimary)
            ->add(Crud::PAGE_INDEX, $globalDanger)
            ->add(Crud::PAGE_INDEX, $globalSuccess)
            ->add(Crud::PAGE_INDEX, $globalText)

            // add custom actions for detail page
            ->add(Crud::PAGE_DETAIL, $warningAction)
            ->add(Crud::PAGE_DETAIL, $primaryAction)
            ->add(Crud::PAGE_DETAIL, $dangerTextAction)
            ->add(Crud::PAGE_DETAIL, $successAction)

            // add custom actions for edit page
            ->add(Crud::PAGE_EDIT, $warningAction)
            ->add(Crud::PAGE_EDIT, $primaryAction)
            ->add(Crud::PAGE_EDIT, $dangerTextAction)
            ->add(Crud::PAGE_EDIT, $successAction)

            // add custom actions for new page
            ->add(Crud::PAGE_NEW, $warningAction)
            ->add(Crud::PAGE_NEW, $primaryAction)
            ->add(Crud::PAGE_NEW, $dangerTextAction)
            ->add(Crud::PAGE_NEW, $successAction);
    }
}
