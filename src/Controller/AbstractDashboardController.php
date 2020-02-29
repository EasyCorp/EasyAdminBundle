<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DashboardConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DetailPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\FormPageConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\IndexPageConfig;
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

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa-home');
    }

    /**
     * @Route("/admin", name="dashboard")
     */
    public function index(): Response
    {
        return $this->render('@EasyAdmin/layout.html.twig');
    }
}
