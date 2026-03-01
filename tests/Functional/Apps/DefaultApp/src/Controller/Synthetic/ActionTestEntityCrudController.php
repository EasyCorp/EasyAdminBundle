<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests action features including displayIf(), custom actions, render modes,
 * HTML attributes, and custom templates.
 *
 * @extends AbstractCrudController<ActionTestEntity>
 */
class ActionTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ActionTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(100);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            BooleanField::new('isActive'),
            BooleanField::new('isDeletable'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        // custom action to activate an entity
        $activateAction = Action::new('activate', 'Activate')
            ->linkToCrudAction('activateEntity')
            ->displayIf(static function (ActionTestEntity $entity): bool {
                return !$entity->isActive();
            })
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check');

        // custom action to deactivate an entity
        $deactivateAction = Action::new('deactivate', 'Deactivate')
            ->linkToCrudAction('deactivateEntity')
            ->displayIf(static function (ActionTestEntity $entity): bool {
                return $entity->isActive();
            })
            ->addCssClass('btn btn-warning')
            ->setIcon('fa fa-times');

        // GLOBAL ACTIONS for testing render modes (global actions render outside the dropdown)

        // global action rendered as button explicitly (tests renderAsButton())
        $buttonAction = Action::new('buttonAction', 'Button')
            ->linkToCrudAction('noop')
            ->createAsGlobalAction()
            ->renderAsButton();

        // global action rendered as link explicitly (tests renderAsLink())
        $linkAction = Action::new('linkAction', 'Link')
            ->linkToCrudAction('noop')
            ->createAsGlobalAction()
            ->renderAsLink();

        // global action rendered as form (tests renderAsForm())
        $formAction = Action::new('formAction', 'Form')
            ->linkToCrudAction('noop')
            ->createAsGlobalAction()
            ->renderAsForm();

        // global action with custom template (tests setTemplatePath())
        $customTemplateAction = Action::new('customTemplate', 'Custom Template')
            ->linkToCrudAction('noop')
            ->createAsGlobalAction()
            ->setTemplatePath('admin/action/custom_action.html.twig');

        // ENTITY ACTIONS for testing dropdown features

        // action with custom HTML attributes (tests setHtmlAttributes() in dropdown)
        $attrAction = Action::new('attrAction', 'With Attrs')
            ->linkToCrudAction('noop')
            ->setHtmlAttributes([
                'data-test' => 'value',
                'aria-label' => 'Custom label',
            ]);

        // action with icon only (no label)
        $iconOnlyAction = Action::new('iconOnly', false)
            ->setIcon('fa fa-cog')
            ->linkToCrudAction('noop');

        // entity action rendered as form in dropdown (tests renderAsForm() in dropdown)
        $entityFormAction = Action::new('entityFormAction', 'Entity Form')
            ->linkToCrudAction('noop')
            ->renderAsForm();

        return $actions
            // add custom entity actions with displayIf()
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_INDEX, $deactivateAction)
            ->add(Crud::PAGE_DETAIL, $activateAction)
            ->add(Crud::PAGE_DETAIL, $deactivateAction)

            // add global actions for render mode tests
            ->add(Crud::PAGE_INDEX, $buttonAction)
            ->add(Crud::PAGE_INDEX, $linkAction)
            ->add(Crud::PAGE_INDEX, $formAction)
            ->add(Crud::PAGE_INDEX, $customTemplateAction)

            // add entity actions for dropdown feature tests
            ->add(Crud::PAGE_INDEX, $attrAction)
            ->add(Crud::PAGE_INDEX, $iconOnlyAction)
            ->add(Crud::PAGE_INDEX, $entityFormAction)

            // disable delete action for non-deletable entities
            ->update(Crud::PAGE_INDEX, Action::DELETE, static function (Action $action) {
                return $action->displayIf(static function (ActionTestEntity $entity): bool {
                    return $entity->isDeletable();
                });
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, static function (Action $action) {
                return $action->displayIf(static function (ActionTestEntity $entity): bool {
                    return $entity->isDeletable();
                });
            });
    }

    public function noop(AdminContext $context): Response
    {
        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/{entityId}/activate-entity', 'activate_entity')]
    public function activateEntity(AdminContext $context): Response
    {
        /** @var ActionTestEntity $entity */
        $entity = $context->getEntity()->getInstance();
        $entity->setIsActive(true);

        $entityManager = $this->container->get('doctrine')->getManagerForClass(ActionTestEntity::class);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Entity "%s" has been activated.', $entity->getName()));

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/{entityId}/deactivate-entity', 'deactivate_entity')]
    public function deactivateEntity(AdminContext $context): Response
    {
        /** @var ActionTestEntity $entity */
        $entity = $context->getEntity()->getInstance();
        $entity->setIsActive(false);

        $entityManager = $this->container->get('doctrine')->getManagerForClass(ActionTestEntity::class);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Entity "%s" has been deactivated.', $entity->getName()));

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }
}
