<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ActionDtoInterface
{
    public function getType(): string;

    public function setType(string $type): void;

    public function isEntityAction(): bool;

    public function isGlobalAction(): bool;

    public function isBatchAction(): bool;

    public function getName(): string;

    public function setName(string $name): void;

    public function getLabel(): TranslatableInterface|string|false|null;

    public function setLabel(TranslatableInterface|string|false|null $label): void;

    public function getIcon(): ?string;

    public function setIcon(?string $icon): void;

    public function getCssClass(): string;

    public function setCssClass(string $cssClass): void;

    public function getAddedCssClass(): string;

    public function setAddedCssClass(string $cssClass): void;

    public function getHtmlElement(): string;

    public function setHtmlElement(string $htmlElement): void;

    public function getHtmlAttributes(): array;

    public function addHtmlAttributes(array $htmlAttributes): void;

    public function setHtmlAttributes(array $htmlAttributes): void;

    public function setHtmlAttribute(string $attributeName, string $attributeValue): void;

    public function getTemplatePath(): ?string;

    public function setTemplatePath(string $templatePath): void;

    public function getLinkUrl(): string;

    public function setLinkUrl(string $linkUrl): void;

    public function getCrudActionName(): ?string;

    public function setCrudActionName(string $crudActionName): void;

    public function getRouteName(): ?string;

    public function setRouteName(string $routeName): void;

    /**
     * @return array|callable
     */
    public function getRouteParameters();

    /**
     * @param array|callable $routeParameters
     */
    public function setRouteParameters($routeParameters): void;

    /**
     * @return string|callable|null
     */
    public function getUrl();

    /**
     * @param string|callable $url
     */
    public function setUrl($url): void;

    public function getTranslationParameters(): array;

    public function setTranslationParameters(array $translationParameters): void;

    public function shouldBeDisplayedFor(EntityDtoInterface $entityDto): bool;

    public function setDisplayCallable(callable $displayCallable): void;

    /**
     * @internal
     */
    public function getAsConfigObject(): Action;
}
