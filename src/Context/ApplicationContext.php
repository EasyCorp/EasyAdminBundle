<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Configuration;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\TemplateRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Builder\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A context object that stores all the config about the current dashboard and resource.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ApplicationContext
{
    private $request;
    private $tokenStorage;
    private $i18nDto;
    private $dashboardDto;
    private $dashboardControllerInstance;
    private $menuBuilder;
    private $mainMenuDto;
    private $userMenuDto;
    private $assetDto;
    private $crudDto;
    private $templateRegistry;

    public function __construct(Request $request, TokenStorageInterface $tokenStorage, I18nDto $i18nDto, DashboardDto $dashboardDto, DashboardControllerInterface $dashboardController, ItemCollectionBuilderInterface $menuBuilder, AssetDto $assetDto, ?CrudDto $crudDto, TemplateRegistry $templateRegistry)
    {
        $this->request = $request;
        $this->tokenStorage = $tokenStorage;
        $this->i18nDto = $i18nDto;
        $this->dashboardDto = $dashboardDto;
        $this->dashboardControllerInstance = $dashboardController;
        $this->menuBuilder = $menuBuilder;
        $this->assetDto = $assetDto;
        $this->crudDto = $crudDto;
        $this->templateRegistry = $templateRegistry;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getI18n(): I18nDto
    {
        return $this->i18nDto;
    }

    public function getUser(): ?UserInterface
    {
        // The code of this method is copied from https://github.com/symfony/twig-bridge/blob/master/AppVariable.php
        // MIT License - (c) Fabien Potencier <fabien@symfony.com>
        if (null === $tokenStorage = $this->tokenStorage) {
            throw new \RuntimeException('The "user" variable is not available.');
        }

        if (!$token = $tokenStorage->getToken()) {
            return null;
        }

        $user = $token->getUser();

        return \is_object($user) ? $user : null;
    }

    public function getAssets(): AssetDto
    {
        return $this->assetDto;
    }

    public function getDashboardTitle(): string
    {
        return $this->dashboardDto->getTitle();
    }

    public function getDashboardFaviconPath(): string
    {
        return $this->dashboardDto->getFaviconPath();
    }

    public function getDashboardRouteName(): string
    {
        return $this->dashboardDto->getRouteName();
    }

    public function getMainMenu(): MainMenuDto
    {
        if (null !== $this->mainMenuDto) {
            return $this->mainMenuDto;
        }

        $mainMenuItems = iterator_to_array($this->dashboardControllerInstance->getMenuItems());
        $builtMainMenuItems = $this->menuBuilder->setItems($mainMenuItems)->build();

        $selectedMenuIndex = $this->getRequest()->query->getInt('menuIndex', -1);
        $selectedMenuSubIndex = $this->getRequest()->query->getInt('submenuIndex', -1);

        return $this->mainMenuDto = new MainMenuDto($builtMainMenuItems, $selectedMenuIndex, $selectedMenuSubIndex);
    }

    public function getUserMenu(): UserMenuDto
    {
        if (null === $this->getUser()) {
            return UserMenuConfig::new()->getAsDto();
        }

        if (null !== $this->userMenuDto) {
            return $this->userMenuDto;
        }

        $userMenuConfig = $this->dashboardControllerInstance->configureUserMenu($this->getUser());
        $userMenuDto = $userMenuConfig->getAsDto();
        $builtUserMenuItems = $this->menuBuilder->setItems($userMenuDto->getItems())->build();

        return $this->userMenuDto = $userMenuDto->with([
            'items' => $builtUserMenuItems,
        ]);
    }

    public function getCrud(): ?CrudDto
    {
        return $this->crudDto;
    }

    public function getTemplatePath(string $templateName): string
    {
        return $this->templateRegistry->get($templateName)->getPath();
    }
}
