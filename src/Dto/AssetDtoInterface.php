<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AssetDtoInterface
{
    public function getValue(): string;

    public function getPackageName(): ?string;

    public function setPackageName(string $packageName): void;

    public function setAsync(bool $async): void;

    public function isAsync(): bool;

    public function setDefer(bool $defer): void;

    public function isDefer(): bool;

    public function setPreload(bool $preload): void;

    public function isPreload(): bool;

    public function setNopush(bool $nopush): void;

    public function isNopush(): bool;

    public function setWebpackPackageName(?string $packageName): void;

    public function getWebpackPackageName(): ?string;

    public function setWebpackEntrypointName(string $entrypointName): void;

    public function getWebpackEntrypointName(): string;

    public function setHtmlAttribute(string $attrName, string $attrValue): void;

    public function getHtmlAttributes(): array;

    public function getLoadedOn(): KeyValueStore;

    public function setLoadedOn(KeyValueStore $loadedOn): void;
}
