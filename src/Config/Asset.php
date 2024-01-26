<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Asset\AssetPackage;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Asset
{
    private AssetDto $dto;

    private function __construct(AssetDto $assetDto)
    {
        $this->dto = $assetDto;
    }

    public function __toString()
    {
        return $this->dto->getValue();
    }

    /**
     * The argument is the 'path' when adding CSS or JS files and the 'entryName' when
     * adding Webpack Encore or ImportMap entries:
     *
     *   Asset::new('build/admin.css')
     *   Asset::new('some/path/admin.js')
     *   Asset::new('admin-app') (Webpack Encore or AssetMapper entry)
     */
    public static function new(string $value): self
    {
        $dto = new AssetDto($value);

        return new self($dto);
    }

    /**
     * Assets provided by EasyAdmin (e.g. 'bundles/easyadmin/app.js') are versioned
     * and managed through a special Symfony Asset named package.
     * Call this method instead of '::new()' when adding those EasyAdmin assets so
     * they use the right package name (which is needed later when calling to 'asset()' Twig function).
     *
     * @param string $value The 'path' when adding CSS or JS files and the 'entryName' when adding Webpack Encore entries
     */
    public static function fromEasyAdminAssetPackage(string $value): self
    {
        return self::new($value)->package(AssetPackage::PACKAGE_NAME);
    }

    public function async(bool $async = true): self
    {
        $this->dto->setAsync($async);

        return $this;
    }

    public function defer(bool $defer = true): self
    {
        $this->dto->setDefer($defer);

        return $this;
    }

    public function preload(bool $preload = true): self
    {
        if (!class_exists('Symfony\\Component\\WebLink\\Link')) {
            throw new \RuntimeException(sprintf('You are trying to preload an asset called "%s" but WebLink component is not installed in your project. Try running "composer require symfony/web-link"', $this->dto->getValue()));
        }

        $this->dto->setPreload($preload);

        return $this;
    }

    public function nopush(bool $nopush = true): self
    {
        if (!class_exists('Symfony\\Component\\WebLink\\Link')) {
            throw new \RuntimeException(sprintf('You are trying to configure the "nopush" preload attribute of an asset called "%s" but WebLink component is not installed in your project. Try running "composer require symfony/web-link"', $this->dto->getValue()));
        }

        $this->dto->setNopush($nopush);

        return $this;
    }

    public function webpackPackageName(?string $packageName = null): self
    {
        $this->dto->setWebpackPackageName($packageName);

        return $this;
    }

    public function webpackEntrypointName(string $entrypointName): self
    {
        $this->dto->setWebpackEntrypointName($entrypointName);

        return $this;
    }

    public function htmlAttr(string $attrName, string $attrValue): self
    {
        $this->dto->setHtmlAttribute($attrName, $attrValue);

        return $this;
    }

    public function htmlAttrs(array $attrNamesAndValues): self
    {
        foreach ($attrNamesAndValues as $attrName => $attrValue) {
            $this->dto->setHtmlAttribute($attrName, $attrValue);
        }

        return $this;
    }

    /**
     * @param string $packageName The name of the Symfony Asset package this asset belongs to
     */
    public function package(string $packageName): self
    {
        $this->dto->setPackageName($packageName);

        return $this;
    }

    public function ignoreOnDetail(): self
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(Crud::PAGE_DETAIL);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreOnForm(): self
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(Crud::PAGE_NEW);
        $loadedOn->delete(Crud::PAGE_EDIT);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreWhenCreating(): self
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(Crud::PAGE_NEW);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreWhenUpdating(): self
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(Crud::PAGE_EDIT);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreOnIndex(): self
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(Crud::PAGE_INDEX);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function onlyOnDetail(): self
    {
        $this->dto->setLoadedOn(KeyValueStore::new([Crud::PAGE_DETAIL => Crud::PAGE_DETAIL]));

        return $this;
    }

    public function onlyOnForms(): self
    {
        $this->dto->setLoadedOn(KeyValueStore::new([
            Crud::PAGE_NEW => Crud::PAGE_NEW,
            Crud::PAGE_EDIT => Crud::PAGE_EDIT,
        ]));

        return $this;
    }

    public function onlyOnIndex(): self
    {
        $this->dto->setLoadedOn(KeyValueStore::new([Crud::PAGE_INDEX => Crud::PAGE_INDEX]));

        return $this;
    }

    public function onlyWhenCreating(): self
    {
        $this->dto->setLoadedOn(KeyValueStore::new([Crud::PAGE_NEW => Crud::PAGE_NEW]));

        return $this;
    }

    public function onlyWhenUpdating(): self
    {
        $this->dto->setLoadedOn(KeyValueStore::new([Crud::PAGE_EDIT => Crud::PAGE_EDIT]));

        return $this;
    }

    public function getAsDto(): AssetDto
    {
        return $this->dto;
    }
}
