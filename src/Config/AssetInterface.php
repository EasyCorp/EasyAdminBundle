<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AssetInterface
{
    public function async(bool $async = true): AssetInterface;

    public function defer(bool $defer = true): AssetInterface;

    public function preload(bool $preload = true): AssetInterface;

    public function nopush(bool $nopush = true): AssetInterface;

    public function webpackPackageName(?string $packageName = null): AssetInterface;

    public function webpackEntrypointName(string $entrypointName): AssetInterface;

    public function htmlAttr(string $attrName, string $attrValue): AssetInterface;

    public function htmlAttrs(array $attrNamesAndValues): AssetInterface;

    /**
     * @param string $packageName The name of the Symfony Asset package this asset belongs to
     */
    public function package(string $packageName): AssetInterface;

    public function ignoreOnDetail(): AssetInterface;

    public function ignoreOnForm(): AssetInterface;

    public function ignoreWhenCreating(): AssetInterface;

    public function ignoreWhenUpdating(): AssetInterface;

    public function ignoreOnIndex(): AssetInterface;

    public function onlyOnDetail(): AssetInterface;

    public function onlyOnForms(): AssetInterface;

    public function onlyOnIndex(): AssetInterface;

    public function onlyWhenCreating(): AssetInterface;

    public function onlyWhenUpdating(): AssetInterface;

    public function getAsDto(): AssetDtoInterface;
}
