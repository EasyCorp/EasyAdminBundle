<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Action groups whose main action or dropdown actions are hidden by displayIf(),
 * by a permission that no user has or because the action is disabled.
 *
 * @extends AbstractCrudController<Category>
 */
class ActionGroupsMainActionCrudController extends AbstractCrudController
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
        $isActive = static fn (Category $category): bool => $category->isActive();

        $conditionalMainGroup = ActionGroup::new('conditional_main_group', 'Conditional Main Group')
            ->addMainAction(Action::new('conditional_main', 'Conditional Main')->linkToCrudAction('edit')->displayIf($isActive))
            ->addAction(Action::new('conditional_main_item', 'Conditional Main Item')->linkToCrudAction('detail'));

        $restrictedMainGroup = ActionGroup::new('restricted_main_group', 'Restricted Main Group')
            ->addMainAction(Action::new('restricted_main', 'Restricted Main')->linkToCrudAction('edit'))
            ->addAction(Action::new('restricted_main_item', 'Restricted Main Item')->linkToCrudAction('detail'));

        $disabledMainGroup = ActionGroup::new('disabled_main_group', 'Disabled Main Group')
            ->addMainAction(Action::new('disabled_main', 'Disabled Main')->linkToCrudAction('edit'))
            ->addAction(Action::new('disabled_main_item', 'Disabled Main Item')->linkToCrudAction('detail'));

        $onlyMainGroup = ActionGroup::new('only_main_group', 'Only Main Group')
            ->addMainAction(Action::new('only_main', 'Only Main')->linkToCrudAction('edit'))
            ->addHeader('Only Main Header')
            ->addAction(Action::new('only_main_item', 'Only Main Item')->linkToCrudAction('detail')->displayIf($isActive));

        $nothingVisibleGroup = ActionGroup::new('nothing_visible_group', 'Nothing Visible Group')
            ->addMainAction(Action::new('nothing_visible_main', 'Nothing Visible Main')->linkToCrudAction('edit')->displayIf($isActive))
            ->addAction(Action::new('nothing_visible_item', 'Nothing Visible Item')->linkToCrudAction('detail')->displayIf($isActive));

        $restrictedMainGlobalGroup = ActionGroup::new('restricted_main_global_group', 'Restricted Main Global Group')
            ->createAsGlobalActionGroup()
            ->addMainAction(Action::new('restricted_main', 'Restricted Main')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('restricted_main_global_item', 'Restricted Main Global Item')->linkToCrudAction('aGlobalAction'));

        $onlyMainGlobalGroup = ActionGroup::new('only_main_global_group', 'Only Main Global Group')
            ->createAsGlobalActionGroup()
            ->addMainAction(Action::new('only_main_global', 'Only Main Global')->linkToCrudAction('aGlobalAction'))
            ->addAction(Action::new('restricted_item', 'Restricted Item')->linkToCrudAction('aGlobalAction'));

        return $actions
            ->add(Crud::PAGE_INDEX, $restrictedMainGlobalGroup)
            ->add(Crud::PAGE_INDEX, $onlyMainGlobalGroup)

            ->add(Crud::PAGE_INDEX, $conditionalMainGroup)
            ->add(Crud::PAGE_INDEX, $restrictedMainGroup)
            ->add(Crud::PAGE_INDEX, $disabledMainGroup)
            ->add(Crud::PAGE_INDEX, $onlyMainGroup)
            ->add(Crud::PAGE_INDEX, $nothingVisibleGroup)

            ->add(Crud::PAGE_DETAIL, $conditionalMainGroup)
            ->add(Crud::PAGE_DETAIL, $restrictedMainGroup)
            ->add(Crud::PAGE_DETAIL, $disabledMainGroup)
            ->add(Crud::PAGE_DETAIL, $onlyMainGroup)
            ->add(Crud::PAGE_DETAIL, $nothingVisibleGroup)

            ->setPermission('restricted_main', 'ROLE_FOO')
            ->setPermission('restricted_item', 'ROLE_FOO')
            ->disable('disabled_main');
    }

    #[AdminRoute('/a-global-action', name: 'a_global_action')]
    public function aGlobalAction(): Response
    {
        return new Response('Global action executed');
    }
}
