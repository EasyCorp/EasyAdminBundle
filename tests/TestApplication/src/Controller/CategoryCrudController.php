<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Config\Action as AppAction;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<Category>
 */
class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $customPageAction = Action::new(AppAction::CUSTOM_ACTION)
            ->createAsGlobalAction()
            ->linkToCrudAction('customPage');

        return $actions->add(Crud::PAGE_INDEX, $customPageAction)
            ->setPermission(AppAction::CUSTOM_ACTION, 'ROLE_ADMIN');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('active');
    }

    /**
     * @param AdminContext<Category> $context
     */
    public function customAction(AdminContext $context): Response
    {
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => AppAction::CUSTOM_ACTION, 'entity' => $context->getEntity()])) {
            throw new ForbiddenActionException($context);
        }

        return new Response();
    }
}
