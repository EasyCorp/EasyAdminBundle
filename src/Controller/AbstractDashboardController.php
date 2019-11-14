<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\DashboardConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\DashboardControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class is useful to extend your dashboard from it instead of implementing
 * the interface.
 */
abstract class AbstractDashboardController extends AbstractController implements DashboardControllerInterface
{
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'translator' => '?'.TranslatorInterface::class,
        ]);
    }

    public function configureDashboard(): DashboardConfig
    {
        return DashboardConfig::new();
    }

    public function configureUserMenu(UserInterface $user): UserMenuConfig
    {
        return UserMenuConfig::new();
        $signOutLabel = $this->get('translator')->trans('user.signout', [], 'EasyAdminBundle');
        $exitImpersonationLabel = $this->get('translator')->trans('user.exit_impersonation', [], 'EasyAdminBundle');

        $userMenuItems = [MenuItem::logout($signOutLabel, 'fa-sign-out')->getAsValueObject()];
        if ($this->isGranted('ROLE_PREVIOUS_ADMIN')) {
            $userMenuItems[] = MenuItem::exitImpersonation($exitImpersonationLabel, 'fa-user-lock')->getAsValueObject();
        }

        return UserMenuConfig::new()
            ->setName(method_exists($user, '__toString') ? (string) $user : $user->getUsername())
            ->setAvatarUrl(null)
            ->setMenuItems($userMenuItems);
    }

    public function configureAssets(): AssetConfig
    {
        return AssetConfig::new();
    }

    public function getMenuItems(): iterable
    {
        yield MenuItem::new('Dashboard', 'fa-home')->homepage();
    }

    /**
     * @Route("/admin", name="dashboard")
     */
    public function index(): Response
    {
        return $this->render('@EasyAdmin/layout.html.twig');
    }
}
