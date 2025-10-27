<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

final class AdminContextProvider implements AdminContextProviderInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * @deprecated since 4.27 and it will be removed in EasyAdmin 5.0 without a replacement
     */
    public function hasContext(): bool
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return null !== $currentRequest && $currentRequest->attributes->has(EA::CONTEXT_REQUEST_ATTRIBUTE);
    }

    public function getContext(bool $throw = false): ?AdminContextInterface
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        if (null === $currentRequest && $throw) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.25.0',
                'The "$throw" argument of the "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Avoid using the EasyAdmin context outside HTTP requests or for non-admin requests.',
                __METHOD__
            );

            throw new \LogicException('Cannot use the EasyAdmin context: no request is available.');
        }

        return $currentRequest?->attributes->get(EA::CONTEXT_REQUEST_ATTRIBUTE);
    }

    public function getRequest(): Request
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getRequest();
    }

    public function getReferrer(): ?string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.8.11',
            'EasyAdmin URLs no longer include the referrer URL. If you still need it, you can get the referrer provided by browsers via $context->getRequest()->headers->get(\'referer\').',
            __METHOD__,
        );

        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getReferrer();
    }

    public function getI18n(): I18nDto
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getI18n();
    }

    public function getCrudControllers(): CrudControllerRegistry
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getCrudControllers();
    }

    public function getEntity(): EntityDto
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getEntity();
    }

    public function getUser(): ?UserInterface
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getUser();
    }

    public function getAssets(): AssetsDto
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getAssets();
    }

    public function getSignedUrls(): bool
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.1.0',
            'EasyAdmin URLs no longer include signatures because they don\'t provide any additional security. The "%s" method will be removed in EasyAdmin 5.0.0, so you should stop using it.',
            __METHOD__
        );

        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getSignedUrls();
    }

    public function getAbsoluteUrls(): bool
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getAbsoluteUrls();
    }

    public function getDashboardTitle(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardTitle();
    }

    public function getDashboardFaviconPath(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardFaviconPath();
    }

    public function getDashboardControllerFqcn(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardControllerFqcn();
    }

    public function getDashboardRouteName(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardRouteName();
    }

    public function getDashboardContentWidth(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardContentWidth();
    }

    public function getDashboardSidebarWidth(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardSidebarWidth();
    }

    public function getDashboardHasDarkModeEnabled(): bool
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardHasDarkModeEnabled();
    }

    public function getDashboardDefaultColorScheme(): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardDefaultColorScheme();
    }

    public function getDashboardLocales(): array
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getDashboardLocales();
    }

    public function getMainMenu(): MainMenuDto
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getMainMenu();
    }

    public function getUserMenu(): UserMenuDto
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getUserMenu();
    }

    public function getCrud(): ?CrudDto
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getCrud();
    }

    public function getSearch(): ?SearchDto
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getSearch();
    }

    public function getTemplatePath(string $templateName): string
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->getTemplatePath($templateName);
    }

    public function usePrettyUrls(): bool
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'The "%s" method is deprecated and will be removed in EasyAdmin 5.0.0. Use the method with the same name from the "EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext" class instead. This deprecation may have been triggered by the usage of the global "ea" variable in a Twig template, which is also deprecated. Use the equivalent "ea()" Twig function instead.',
            __METHOD__
        );

        return $this->getContext(true)->usePrettyUrls();
    }
}
