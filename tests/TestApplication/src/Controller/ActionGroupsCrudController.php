<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use function Symfony\Component\Translation\t;

/**
 * Tests the ActionGroup functionality across different CRUD pages.
 *
 * @extends AbstractCrudController<Category>
 */
class ActionGroupsCrudController extends AbstractCrudController
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
        // basic action group with simple actions
        $group1 = ActionGroup::new('group1', 'Action Group 1')
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('edit'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('detail'))
            ->addAction(Action::new('action3', 'Action 3')->linkToCrudAction('delete'));
        $group1Global = ActionGroup::new('group1global', 'Global Action Group 1')
            ->createAsGlobalActionGroup()
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action3', 'Action 3')->linkToCrudAction('aGlobalAction'));

        // split button with main action
        $group2 = ActionGroup::new('group2', 'Action Group 2')
            ->addMainAction(Action::new('main_action', 'Main Action')->linkToCrudAction('edit'))
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('detail'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('delete'));
        $group2Global = ActionGroup::new('group2global', 'Global Action Group 2')
            ->createAsGlobalActionGroup()
            ->addMainAction(Action::new('main_action', 'Main Action')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('aGlobalAction'));

        // action group with headers and dividers
        $group3 = ActionGroup::new('group3', 'Action Group 3')
            ->addHeader('Group 1')
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('edit'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('detail'))
            ->addDivider()
            ->addHeader('Group 2')
            ->addAction(Action::new('action3', 'Action 3')->linkToCrudAction('delete'));
        $group3Global = ActionGroup::new('group3global', 'Global Action Group 3')
            ->createAsGlobalActionGroup()
            ->addHeader('Group 1')
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('aGlobalAction'))
            ->addDivider()
            ->addHeader('Group 2')
            ->addAction(Action::new('action3', 'Action 3')->linkToCrudAction('aGlobalAction'));

        // mixed action types (link, button, form)
        $group4 = ActionGroup::new('group4', 'Action Group 4')
            ->addAction(Action::new('link_action', 'Action 1')->linkToCrudAction('edit'))
            ->addAction(Action::new('button_action', 'Action 2')->linkToCrudAction('detail')->addCssClass('btn-primary'))
            ->addAction(Action::new('form_action', 'Action 3')->linkToCrudAction('delete')->displayAsForm());
        $group4Global = ActionGroup::new('group4global ', 'Global Action Group 4')
            ->createAsGlobalActionGroup()
            ->addAction(Action::new('link_action', 'Action 1')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('button_action', 'Action 2')->linkToCrudAction('aGlobalAction')->addCssClass('btn-primary'))
            ->addAction(Action::new('form_action', 'Action 3')->linkToCrudAction('aGlobalAction')->displayAsForm());

        // custom styling
        $group5 = ActionGroup::new('group5', 'Action Group 5')
            ->setCssClass('custom-dropdown')
            ->addCssClass('additional-class')
            ->setHtmlAttributes([
                'data-test' => 'group-5',
                'data-custom' => 'value',
            ])
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('edit'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('detail'));
        $group5Global = ActionGroup::new('group5global', 'Global Action Group 5')
            ->createAsGlobalActionGroup()
            ->setCssClass('custom-dropdown')
            ->addCssClass('additional-class')
            ->setHtmlAttributes([
                'data-test' => 'group-5',
                'data-custom' => 'value',
            ])
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('aGlobalAction'));

        // conditional display
        $group6 = ActionGroup::new('group6', 'Action Group 6')
            ->displayIf(static fn ($entity): bool => $entity instanceof Category && str_starts_with($entity->getName(), 'Category 1'))
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('edit'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('detail'));
        $group6Global = ActionGroup::new('group6global', 'Global Action Group 6')
            ->createAsGlobalActionGroup()
            ->displayIf(static fn ($entity): bool => $entity instanceof Category && str_starts_with($entity->getName(), 'Category 1'))
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('aGlobalAction'));

        // action group with icon only (no label)
        $group7 = ActionGroup::new('group7', false, 'fa fa-ellipsis-v')
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('edit'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('detail'));
        $group7Global = ActionGroup::new('group7global', false, 'fa fa-ellipsis-v')
            ->createAsGlobalActionGroup()
            ->addAction(Action::new('action1', 'Action 1')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('action2', 'Action 2')->linkToCrudAction('aGlobalAction'));

        // action group with translatable labels
        $group8 = ActionGroup::new('group8', t('Action Group 8'))
            ->addHeader(t('Group 1'))
            ->addAction(Action::new('action1', t('Action 1'))->linkToCrudAction('edit'))
            ->addAction(Action::new('action2', t('Action 2'))->linkToCrudAction('detail'));

        // complex action group with everything
        $group9 = ActionGroup::new('group9', 'Action Group 9', 'fa fa-cog')
            ->addMainAction(Action::new('main', 'Main Action')->linkToCrudAction('edit'))
            ->addHeader('Quick Actions')
            ->addAction(Action::new('quick1', 'Action 1')->linkToCrudAction('detail'))
            ->addAction(Action::new('quick2', 'Action 2')->linkToUrl('#')->addCssClass('text-primary'))
            ->addDivider()
            ->addHeader('Danger Zone')
            ->addAction(Action::new('delete', 'Action 3')->linkToCrudAction('delete')->addCssClass('text-danger'))
            ->setCssClass('group-complex')
            ->setHtmlAttributes(['data-bs-toggle' => 'tooltip', 'title' => 'Complex dropdown']);

        return $actions
            // global action groups (shown in page actions area)
            ->add(Crud::PAGE_INDEX, $group1Global)
            ->add(Crud::PAGE_INDEX, $group2Global)
            ->add(Crud::PAGE_INDEX, $group3Global)
            ->add(Crud::PAGE_INDEX, $group4Global)
            ->add(Crud::PAGE_INDEX, $group5Global)
            ->add(Crud::PAGE_INDEX, $group6Global)
            ->add(Crud::PAGE_INDEX, $group7Global)

            // entity action groups (shown in table row dropdowns as submenus)
            ->add(Crud::PAGE_INDEX, $group1)
            ->add(Crud::PAGE_INDEX, $group2)
            ->add(Crud::PAGE_INDEX, $group3)
            ->add(Crud::PAGE_INDEX, $group6)

            ->add(Crud::PAGE_DETAIL, $group1)
            ->add(Crud::PAGE_DETAIL, $group2)
            ->add(Crud::PAGE_DETAIL, $group6)
            ->add(Crud::PAGE_DETAIL, $group8)
            ->add(Crud::PAGE_DETAIL, $group9)

            ->add(Crud::PAGE_EDIT, $group1)
            ->add(Crud::PAGE_EDIT, $group2)
            ->add(Crud::PAGE_EDIT, $group5)
            ->add(Crud::PAGE_EDIT, $group9)

            ->add(Crud::PAGE_NEW, $group1)
            ->add(Crud::PAGE_NEW, $group3)
            ->add(Crud::PAGE_NEW, $group8)

            // test CSS class combinations with regular actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setCssClass('btn btn-sm btn-primary');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->addCssClass('text-danger');
            });
    }

    public function aGlobalAction(): void
    {
        // no need to add any logic here because the action won't be executed in the tests
    }
}
