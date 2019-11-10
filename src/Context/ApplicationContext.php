<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\AssetCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Configuration;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityConfig;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\UserMenuConfig;
use EasyCorp\Bundle\EasyAdminBundle\Dashboard\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuBuilderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItemInterface;
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
    private $assetCollection;
    private $crudConfig;
    private $crudPage;
    private $pageConfig;
    private $entity;
    private $entityConfig;

    public function __construct(Request $request, TokenStorageInterface $tokenStorage, DashboardControllerInterface $dashboard, MenuBuilderInterface $menuBuilder, AssetCollection $assetCollection, ?CrudConfig $crudConfig, ?string $crudPage, $pageConfig, ?EntityConfig $entityConfig, $entity)
    {
        $this->request = $request;
        $this->tokenStorage = $tokenStorage;
        $this->dashboardControllerInstance = $dashboard;
        $this->menuBuilder = $menuBuilder;
        $this->assetCollection = $assetCollection;
        $this->crudConfig = $crudConfig;
        $this->crudPage = $crudPage;
        $this->pageConfig = $pageConfig;
        $this->entityConfig = $entityConfig;
        $this->entity = $entity;

        $userMenuConfig = null === $this->getUser() ? UserMenuConfig::new()->getAsValueObject() : $dashboard->configureUserMenu($this->getUser())->getAsValueObject();
        $this->config = new Configuration($dashboard->configureDashboard(), $assetCollection, $userMenuConfig, $crudConfig, $pageConfig, $this->menuBuilder, $request->getLocale());
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
     * @return MenuItemInterface[]
     */
    public function getMenu(): array
    {
        $mainMenuItems = iterator_to_array($this->dashboardControllerInstance->getMenuItems());

        return $this->menuBuilder->setItems($mainMenuItems)->build();
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
    public function getPage(): ?string
    {
        return $this->crudPage;
    }

    public function getTransParameters(): array
    {
        if (null === $this->crudConfig || null === $this->getEntity()) {
            return [];
        }

        return [
            '%entity_label_singular%' => $this->crudConfig->getLabelInSingular(),
            '%entity_label_plural%' => $this->crudConfig->getLabelInPlural(),
            '%entity_name%' => $this->crudConfig->getLabelInPlural(),
            '%entity_id%' => $this->getEntityConfig()->getId(),
        ];
    }

    /**
     * @return object|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getEntityConfig(): ?EntityConfig
    {
        return $this->entityConfig;
    }

    public function getDashboardRouteName(): string
    {
        return $this->request->attributes->get('_route');
    }
}
