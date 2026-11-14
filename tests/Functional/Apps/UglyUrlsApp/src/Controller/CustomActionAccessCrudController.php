<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used by UglyUrlsCustomActionAccessTest. None of the custom action methods check
 * permissions themselves, so any denied access comes from EasyAdmin.
 *
 * @extends AbstractCrudController<Category>
 */
class CustomActionAccessCrudController extends AbstractCrudController
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
        $approve = Action::new('approve')->linkToCrudAction('approveCategory');
        $archive = Action::new('archive')->linkToCrudAction('archiveCategory');
        $activateSelected = Action::new('activateSelected')->linkToCrudAction('activateCategories');

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $archive)
            ->addBatchAction($activateSelected)
            ->setPermission('approve', 'ROLE_ADMIN')
            ->setPermission('activateSelected', 'ROLE_ADMIN')
            ->disable('archive');
    }

    public function approveCategory(): Response
    {
        return new Response('Category approved');
    }

    public function archiveCategory(): Response
    {
        return new Response('Category archived');
    }

    public function activateCategories(): Response
    {
        return new Response('Categories activated');
    }

    public function showStats(): Response
    {
        return new Response('Category stats');
    }
}
