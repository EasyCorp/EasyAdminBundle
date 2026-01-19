<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A context object that stores all the state and config of the current admin request.
 *
 * Technically, it's a façade over other specialized sub-contexts:
 *
 * - RequestContext: HTTP request and user information
 * - CrudContext: CRUD operation state (entity, fields, search, etc.)
 * - DashboardContext: Dashboard configuration and menus
 * - I18nContext: Internationalization and templates
 *
 * IMPORTANT: any new methods added here MUST be duplicated in the AdminContextProvider class.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TEntity of object
 *
 * @implements AdminContextInterface<TEntity>
 */
final class AdminContext implements AdminContextInterface
{
    public function __construct(
        private readonly RequestContext $requestContext,
        private readonly CrudContext $crudContext,
        private readonly DashboardContext $dashboardContext,
        private readonly I18nContext $i18nContext,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->requestContext->getRequest();
    }

    public function getUser(): ?UserInterface
    {
        return $this->requestContext->getUser();
    }

    public function getReferrer(): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.8.11',
            'EasyAdmin URLs no longer include the referrer URL. If you still need it, you can get the referrer provided by browsers via $context->getRequest()->headers->get(\'referer\').',
            __METHOD__,
        );

        $referrer = $this->requestContext->getRequest()->query->get(EA::REFERRER);

        return '' !== $referrer ? $referrer : null;
    }

    public function getCrud(): ?CrudDto
    {
        return $this->crudContext->getCrud();
    }

    public function getEntity(): EntityDto
    {
        // The interface requires non-null return, but CrudContext may return null
        // if there's no CRUD operation. This maintains backward compatibility.
        return $this->crudContext->getEntity() ?? throw new \LogicException('Cannot get entity outside of a CRUD context. Check if getCrud() returns a value before calling getEntity().');
    }

    public function getSearch(): ?SearchDto
    {
        return $this->crudContext->getSearch();
    }

    public function getCrudControllers(): CrudControllerRegistry
    {
        return $this->crudContext->getCrudControllers();
    }

    public function getMainMenu(): MainMenuDto
    {
        return $this->dashboardContext->getMainMenu();
    }

    public function getUserMenu(): UserMenuDto
    {
        return $this->dashboardContext->getUserMenu();
    }

    public function getAssets(): AssetsDto
    {
        return $this->dashboardContext->getAssets();
    }

    public function usePrettyUrls(): bool
    {
        return $this->dashboardContext->usePrettyUrls();
    }

    public function getDashboardTitle(): string
    {
        return $this->dashboardContext->getDashboardDto()->getTitle();
    }

    public function getDashboardFaviconPath(): string
    {
        return $this->dashboardContext->getDashboardDto()->getFaviconPath();
    }

    public function getDashboardControllerFqcn(): string
    {
        return $this->dashboardContext->getDashboardControllerFqcn();
    }

    public function getDashboardRouteName(): string
    {
        return $this->dashboardContext->getDashboardDto()->getRouteName();
    }

    public function getDashboardContentWidth(): string
    {
        return $this->dashboardContext->getDashboardDto()->getContentWidth();
    }

    public function getDashboardSidebarWidth(): string
    {
        return $this->dashboardContext->getDashboardDto()->getSidebarWidth();
    }

    public function getDashboardHasDarkModeEnabled(): bool
    {
        return $this->dashboardContext->getDashboardDto()->isDarkModeEnabled();
    }

    public function getDashboardDefaultColorScheme(): string
    {
        return $this->dashboardContext->getDashboardDto()->getDefaultColorScheme();
    }

    /**
     * @return LocaleDto[]
     */
    public function getDashboardLocales(): array
    {
        return $this->dashboardContext->getDashboardDto()->getLocales();
    }

    public function getSignedUrls(): bool
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.1.0',
            'EasyAdmin URLs no longer include signatures because they don\'t provide any additional security. The "%s" method will be removed in EasyAdmin 5.0.0, so you should stop using it.',
            __METHOD__
        );

        return $this->dashboardContext->getDashboardDto()->getSignedUrls();
    }

    public function getAbsoluteUrls(): bool
    {
        return $this->dashboardContext->getDashboardDto()->getAbsoluteUrls();
    }

    public function getI18n(): I18nDto
    {
        return $this->i18nContext->getI18n();
    }

    public function getTemplatePath(string $templateName): string
    {
        return $this->i18nContext->getTemplatePath($templateName);
    }

    /**
     * Creates an AdminContext instance suitable for testing.
     *
     * This method provides sensible defaults for all sub-contexts, making it easy
     * to create AdminContext instances in tests without complex setup.
     *
     * @param RequestContext|null   $requestContext   Custom request context (defaults to empty request, no user)
     * @param CrudContext|null      $crudContext      Custom CRUD context (defaults to empty CrudDto)
     * @param DashboardContext|null $dashboardContext Custom dashboard context (defaults to empty dashboard)
     * @param I18nContext|null      $i18nContext      Custom i18n context (defaults to 'en' locale)
     */
    public static function forTesting(
        ?RequestContext $requestContext = null,
        ?CrudContext $crudContext = null,
        ?DashboardContext $dashboardContext = null,
        ?I18nContext $i18nContext = null,
    ): self {
        return new self(
            $requestContext ?? RequestContext::forTesting(),
            $crudContext ?? CrudContext::forTesting(),
            $dashboardContext ?? DashboardContext::forTesting(),
            $i18nContext ?? I18nContext::forTesting(),
        );
    }
}
