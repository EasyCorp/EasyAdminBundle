<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used by CustomActionAccessTest. None of the custom action methods check
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
        $publishFromIndex = Action::new('publishFromIndex')->linkToCrudAction('publishCategory');
        $publishFromDetail = Action::new('publishFromDetail')->linkToCrudAction('publishCategory');
        $activateSelected = Action::new('activateSelected')->linkToCrudAction('activateCategories');
        $sendReport = Action::new('sendReport')->createAsGlobalAction()->linkToRoute('secured_admin_custom_action_access_send_report');
        $sendReminder = Action::new('sendReminder')->createAsGlobalAction()->linkToRoute('send_reminder_alias');
        $exportGroup = ActionGroup::new('export')
            ->createAsGlobalActionGroup()
            ->addAction(Action::new('exportCsv')->linkToCrudAction('exportCategories'));

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $archive)
            ->add(Crud::PAGE_INDEX, $publishFromIndex)
            ->add(Crud::PAGE_DETAIL, $publishFromDetail)
            ->add(Crud::PAGE_INDEX, $exportGroup)
            ->add(Crud::PAGE_INDEX, $sendReport)
            ->add(Crud::PAGE_INDEX, $sendReminder)
            ->addBatchAction($activateSelected)
            ->setPermission('approve', 'ROLE_ADMIN')
            ->setPermission('publishFromIndex', 'ROLE_SUPER_ADMIN')
            ->setPermission('publishFromDetail', 'ROLE_ADMIN')
            ->setPermission('activateSelected', 'ROLE_ADMIN')
            ->setPermission('exportCsv', 'ROLE_ADMIN')
            ->setPermission('sendReport', 'ROLE_ADMIN')
            ->setPermission('sendReminder', 'ROLE_ADMIN')
            ->disable('archive', Action::NEW);
    }

    #[AdminRoute]
    public function approveCategory(): Response
    {
        return new Response('Category approved');
    }

    #[AdminRoute]
    public function archiveCategory(): Response
    {
        return new Response('Category archived');
    }

    #[AdminRoute]
    public function publishCategory(): Response
    {
        return new Response('Category published');
    }

    #[AdminRoute]
    public function activateCategories(Request $request): Response
    {
        return new Response(sprintf('Categories activated (%s)', $request->request->getString('sentinel')));
    }

    #[AdminRoute]
    public function exportCategories(): Response
    {
        return new Response('Categories exported');
    }

    #[AdminRoute('/send-report', name: 'send_report')]
    public function sendReport(): Response
    {
        return new Response('Report sent');
    }

    #[AdminRoute('/send-reminder', name: 'send_reminder')]
    public function sendReminder(): Response
    {
        return new Response('Reminder sent');
    }

    #[AdminRoute]
    public function showStats(): Response
    {
        return new Response('Category stats');
    }
}
