<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\Configuration;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\ItemCollectionBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
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
    public const ATTRIBUTE_KEY = 'easyadmin_context';

    private $config;
    private $request;
    private $tokenStorage;
    private $dashboardControllerInstance;
    private $menuBuilder;
    private $actionBuilder;
    private $assetDto;
    private $crudDto;
    private $crudPageDto;
    private $entityDto;

    public function __construct(Request $request, TokenStorageInterface $tokenStorage, DashboardControllerInterface $dashboardController, ItemCollectionBuilderInterface $menuBuilder, ItemCollectionBuilderInterface $actionBuilder, AssetDto $assetDto, ?CrudDto $crudDto, ?CrudPageDto $crudPageDto, ?EntityDto $entityDto)
    {
        $this->request = $request;
        $this->tokenStorage = $tokenStorage;
        $this->dashboardControllerInstance = $dashboardController;
        $this->menuBuilder = $menuBuilder;
        $this->actionBuilder = $actionBuilder;
        $this->assetDto = $assetDto;
        $this->crudDto = $crudDto;
        $this->crudPageDto = $crudPageDto;
        $this->entityDto = $entityDto;

        $dashboardDto = $dashboardController->configureDashboard()->getAsDto();
        $this->config = new Configuration($dashboardDto, $assetDto, $crudDto, $crudPageDto, $request->getLocale());
    }

    public function getConfig(): Configuration
    {
        return $this->config;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getLocale(bool $languageOnly = false): string
    {
        $fullLocale = $this->getRequest()->getLocale();
        $localeLanguage = strtok($fullLocale, '-_');
        $locale = $languageOnly ? $localeLanguage : $fullLocale;

        return empty($locale) ? 'en' : $locale;
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

    /**
     * @return \EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto[]
     */
    public function getMainMenu(): array
    {
        $mainMenuItems = iterator_to_array($this->dashboardControllerInstance->getMenuItems());

        return $this->menuBuilder->setItems($mainMenuItems)->build();
    }

    /**
     * @return \EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto
     */
    public function getUserMenu(): UserMenuDto
    {
        if (null === $this->getUser()) {
            return UserMenuConfig::new()->getAsDto();
        }

        $userMenuConfig = $this->dashboardControllerInstance->configureUserMenu($this->getUser());
        $userMenuContext = $userMenuConfig->getAsDto();
        $builtUserMenuItems = $this->menuBuilder->setItems($userMenuContext->getItems())->build();

        return $userMenuContext->withProperties([
            'items' => $builtUserMenuItems,
        ]);
    }

    public function getSelectedMenuIndex(): ?int
    {
        return $this->getRequest()->query->getInt('menuIndex', -1);
    }

    public function getSelectedSubMenuIndex(): ?int
    {
        return $this->getRequest()->query->getInt('submenuIndex', -1);
    }

    /**
     * Returns the name of the current CRUD page, if any (e.g. 'detail')
     */
    public function getPageName(): ?string
    {
        if (null === $this->crudPageDto) {
            return null;
        }

        return $this->crudPageDto->getPageName();
    }

    public function getTransParameters(): array
    {
        if (null === $this->crudDto || null === $this->getEntity()) {
            return [];
        }

        return [
            '%entity_label_singular%' => $this->crudDto->getLabelInSingular(),
            '%entity_label_plural%' => $this->crudDto->getLabelInPlural(),
            '%entity_name%' => $this->crudDto->getLabelInPlural(),
            '%entity_id%' => $this->getEntity()->getIdValue(),
        ];
    }

    public function getEntity(): ?EntityDto
    {
        return $this->entityDto;
    }

    /**
     * @return ActionDto[]
     */
    public function getActions(): ?array
    {
        if (null === $this->crudPageDto) {
            return [];
        }

        return $this->actionBuilder->setItems($this->crudPageDto->getActions())->build();
    }

    public function getDashboardRouteName(): string
    {
        return $this->request->attributes->get('_route');
    }
}
