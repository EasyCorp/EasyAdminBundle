<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

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
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests action features including displayIf(), custom actions, render modes,
 * HTML attributes, and custom templates.
 *
 * @extends AbstractCrudController<Category>
 */
class EntityActionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
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
            BooleanField::new('active'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $activateAction = Action::new('activate', 'Activate')
            ->linkToCrudAction('activateEntity')
            ->displayIf(static function (Category $entity): bool {
                return !$entity->isActive();
            })
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check');

        $deactivateAction = Action::new('deactivate', 'Deactivate')
            ->linkToCrudAction('deactivateEntity')
            ->displayIf(static function (Category $entity): bool {
                return $entity->isActive();
            })
            ->addCssClass('btn btn-warning')
            ->setIcon('fa fa-times');

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

            // categories that have blog posts can't be deleted
            ->update(Crud::PAGE_INDEX, Action::DELETE, static function (Action $action) {
                return $action->displayIf(static function (Category $entity): bool {
                    return $entity->getBlogPosts()->isEmpty();
                });
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, static function (Action $action) {
                return $action->displayIf(static function (Category $entity): bool {
                    return $entity->getBlogPosts()->isEmpty();
                });
            });
    }

    #[AdminRoute]
    public function noop(AdminContext $context): Response
    {
        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/{entityId}/activate-entity', 'activate_entity')]
    public function activateEntity(AdminContext $context): Response
    {
        /** @var Category $entity */
        $entity = $context->getEntity()->getInstance();
        $entity->setActive(true);

        $entityManager = $this->container->get('doctrine')->getManagerForClass(Category::class);
        $entityManager->flush();

        // use the structured flash message format to test the 'title' and 'icon' options
        $this->addFlash('success', [
            'message' => sprintf('Entity "%s" has been activated.', $entity->getName()),
            'title' => 'Activation',
            'icon' => 'internal:check',
        ]);

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/{entityId}/deactivate-entity', 'deactivate_entity')]
    public function deactivateEntity(AdminContext $context): Response
    {
        /** @var Category $entity */
        $entity = $context->getEntity()->getInstance();
        $entity->setActive(false);

        $entityManager = $this->container->get('doctrine')->getManagerForClass(Category::class);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Entity "%s" has been deactivated.', $entity->getName()));

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }
}
