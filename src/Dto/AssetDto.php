<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AssetDto
{
    private $value;
    private $async;
    private $defer;
    private $preload;
    private $nopush;
    private $webpackPackageName;
    private $webpackEntrypointName;
    private $htmlAttributes;
    private $loadedOn;

    public function __construct(string $value)
    {
        $this->value = $value;
        $this->async = false;
        $this->defer = false;
        $this->preload = false;
        $this->nopush = false;
        $this->webpackPackageName = null;
        $this->webpackEntrypointName = '_default';
        $this->htmlAttributes = [];
        $this->loadedOn = KeyValueStore::new([
            Crud::PAGE_INDEX => Crud::PAGE_INDEX,
            Crud::PAGE_DETAIL => Crud::PAGE_DETAIL,
            Crud::PAGE_EDIT => Crud::PAGE_EDIT,
            Crud::PAGE_NEW => Crud::PAGE_NEW,
        ]);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setAsync(bool $async): void
    {
        $this->async = $async;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function setDefer(bool $defer): void
    {
        $this->defer = $defer;
    }

    public function isDefer(): bool
    {
        return $this->defer;
    }

    public function setPreload(bool $preload): void
    {
        $this->preload = $preload;
    }

    public function isPreload(): bool
    {
        return $this->preload;
    }

    public function setNopush(bool $nopush): void
    {
        $this->nopush = $nopush;
    }

    public function isNopush(): bool
    {
        return $this->nopush;
    }

    public function setWebpackPackageName(?string $packageName): void
    {
        $this->webpackPackageName = $packageName;
    }

    public function getWebpackPackageName(): ?string
    {
        return $this->webpackPackageName;
    }

    public function setWebpackEntrypointName(string $entrypointName): void
    {
        $this->webpackEntrypointName = $entrypointName;
    }

    public function getWebpackEntrypointName(): string
    {
        return $this->webpackEntrypointName;
    }

    public function setHtmlAttribute(string $attrName, string $attrValue): void
    {
        $this->htmlAttributes[$attrName] = $attrValue;
    }

    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    public function getLoadedOn(): KeyValueStore
    {
        return $this->loadedOn;
    }

    public function setLoadedOn(KeyValueStore $loadedOn): void
    {
        $this->loadedOn = $loadedOn;
    }
}
