<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DashboardConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This class is useful to extend your dashboard from it instead of implementing
 * the interface.
 */
abstract class AbstractDashboardController extends AbstractController implements DashboardControllerInterface
{
    public function configureDashboard(): DashboardConfig
    {
        return DashboardConfig::new();
    }

    public function configureUserMenu(UserInterface $user): UserMenuConfig
    {
        $userMenuItems = [MenuItem::linkToLogout('user.signout', 'fa-sign-out')->setTranslationDomain('EasyAdminBundle')];
        if ($this->isGranted(Permission::EA_EXIT_IMPERSONATION)) {
            $userMenuItems[] = MenuItem::linkToExitImpersonation('user.exit_impersonation', 'fa-user-lock')->setTranslationDomain('EasyAdminBundle');
        }

        return UserMenuConfig::new()
            ->displayUserName()
            ->displayUserAvatar()
            ->setName(method_exists($user, '__toString') ? (string) $user : $user->getUsername())
            ->setAvatarUrl(null)
            ->setMenuItems($userMenuItems);
    }

    public function configureAssets(): AssetConfig
    {
        return AssetConfig::new();
    }

    public function configureCrud(): CrudConfig
    {
        return CrudConfig::new();
    }

    public function configureActions(): ActionConfig
    {
        return ActionConfig::new()
            ->addAction(CrudConfig::PAGE_INDEX, Action::NEW)
            ->addAction(CrudConfig::PAGE_INDEX, Action::EDIT)
            ->addAction(CrudConfig::PAGE_INDEX, Action::DELETE)

            ->addAction(CrudConfig::PAGE_DETAIL, Action::EDIT)
            ->addAction(CrudConfig::PAGE_DETAIL, Action::INDEX)
            ->addAction(CrudConfig::PAGE_DETAIL, Action::DELETE)

            ->addAction(CrudConfig::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->addAction(CrudConfig::PAGE_EDIT, Action::SAVE_AND_CONTINUE)

            ->addAction(CrudConfig::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->addAction(CrudConfig::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa-home');
    }

    /**
     * @Route("/admin", name="dashboard")
     */
    public function index(): Response
    {
        return $this->render('@EasyAdmin/page/dashboard_simple.html.twig');
    }
}
