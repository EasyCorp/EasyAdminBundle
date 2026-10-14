<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;
use function Symfony\Component\Translation\t;

/**
 * Tests the configureActions() method and the generated actions.
 *
 * @extends AbstractCrudController<ActionTestEntity>
 */
class ActionsCustomizationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ActionTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $action1 = Action::new('action1')->linkToCrudAction(Action::DETAIL);
        $action2 = Action::new('action2')->linkToCrudAction(Action::DETAIL)->setCssClass('foo');
        $action3 = Action::new('action3')->linkToCrudAction(Action::DETAIL)->addCssClass('bar');
        $action4 = Action::new('action4')->linkToCrudAction(Action::DETAIL)->setCssClass('foo')->addCssClass('bar');
        $action5 = Action::new('action5')->linkToCrudAction(Action::DETAIL)->setLabel(static fn (ActionTestEntity $entity) => 'Action 5: '.$entity->getName());

        $labelGenerator = new ActionLabelGenerator();
        $action6 = Action::new('action6')->linkToCrudAction(Action::DETAIL)->setLabel(static fn (ActionTestEntity $entity) => $labelGenerator->generateLabel($entity));

        $action7 = Action::new('action7')->linkToCrudAction(Action::DETAIL)->setLabel(static fn (ActionTestEntity $entity) => t('Action %number%: %name%', ['%number%' => 7, '%name%' => $entity->getName()]));

        // this tests that the 'Reset' label is interpreted as a string and not as a callable to the PHP reset() function
        $action8 = Action::new('action8')->linkToCrudAction(Action::DETAIL)->setLabel('Reset');

        $action9 = Action::new('action9')->linkToCrudAction(Action::INDEX)->createAsGlobalAction()->renderAsForm();

        $action10 = Action::new('action10')->linkToCrudAction(Action::DETAIL)->askConfirmation();
        $action11 = Action::new('action11')->linkToCrudAction(Action::DETAIL)->askConfirmation('Visitors will not see this item anymore.');

        return $actions
            ->add(Crud::PAGE_INDEX, $action1)
            ->add(Crud::PAGE_INDEX, $action2)
            ->add(Crud::PAGE_INDEX, $action3)
            ->add(Crud::PAGE_INDEX, $action4)
            ->add(Crud::PAGE_INDEX, $action5)
            ->add(Crud::PAGE_INDEX, $action6)
            ->add(Crud::PAGE_INDEX, $action7)
            ->add(Crud::PAGE_INDEX, $action8)
            ->add(Crud::PAGE_INDEX, $action9)
            ->add(Crud::PAGE_INDEX, $action10)
            ->add(Crud::PAGE_INDEX, $action11)
            ->update(Crud::PAGE_DETAIL, Action::DELETE, static function (Action $action) {
                return $action->askConfirmation('All the comments of this item will be deleted too.');
            })
            ->update(Crud::PAGE_INDEX, Action::NEW, static function (Action $action) {
                return $action->setIcon('fa fa-fw fa-plus')->setLabel(false);
            })
        ;
    }
}

final class ActionLabelGenerator
{
    public function generateLabel(ActionTestEntity $entity): string
    {
        return 'Action 6: '.$entity->getName();
    }
}
