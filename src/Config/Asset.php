<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Asset\AssetPackage;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDtoInterface;

use function Symfony\Component\String\u;

final class Asset implements AssetInterface
{
    private AssetDtoInterface $dto;

    private function __construct(AssetDtoInterface $assetDto)
    {
        $this->dto = $assetDto;
    }

    public function __toString()
    {
        return $this->dto->getValue();
    }

    /**
     * @param string $value The 'path' when adding CSS or JS files and the 'entryName' when adding Webpack Encore entries
     */
    public static function new(string $value): AssetInterface
    {
        $isWebpackEncoreEntry = !u($value)->endsWith('.css') && !u($value)->endsWith('.js');
        if ($isWebpackEncoreEntry && !class_exists('Symfony\\WebpackEncoreBundle\\WebpackEncoreBundle')) {
            throw new \RuntimeException(sprintf('You are trying to add a Webpack Encore entry called "%s" but WebpackEncoreBundle is not installed in your project. Try running "composer require symfony/webpack-encore-bundle"', $value));
        }

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
    public static function fromEasyAdminAssetPackage(string $value): AssetInterface
    {
        return self::new($value)->package(AssetPackage::PACKAGE_NAME);
    }

    public function async(bool $async = true): AssetInterface
    {
        $this->dto->setAsync($async);

        return $this;
    }

    public function defer(bool $defer = true): AssetInterface
    {
        $this->dto->setDefer($defer);

        return $this;
    }

    public function preload(bool $preload = true): AssetInterface
    {
        if (!class_exists('Symfony\\Component\\WebLink\\Link')) {
            throw new \RuntimeException(sprintf('You are trying to preload an asset called "%s" but WebLink component is not installed in your project. Try running "composer require symfony/web-link"', $this->dto->getValue()));
        }

        $this->dto->setPreload($preload);

        return $this;
    }

    public function nopush(bool $nopush = true): AssetInterface
    {
        if (!class_exists('Symfony\\Component\\WebLink\\Link')) {
            throw new \RuntimeException(sprintf('You are trying to configure the "nopush" preload attribute of an asset called "%s" but WebLink component is not installed in your project. Try running "composer require symfony/web-link"', $this->dto->getValue()));
        }

        $this->dto->setNopush($nopush);

        return $this;
    }

    public function webpackPackageName(?string $packageName = null): AssetInterface
    {
        $this->dto->setWebpackPackageName($packageName);

        return $this;
    }

    public function webpackEntrypointName(string $entrypointName): AssetInterface
    {
        $this->dto->setWebpackEntrypointName($entrypointName);

        return $this;
    }

    public function htmlAttr(string $attrName, string $attrValue): AssetInterface
    {
        $this->dto->setHtmlAttribute($attrName, $attrValue);

        return $this;
    }

    public function htmlAttrs(array $attrNamesAndValues): AssetInterface
    {
        foreach ($attrNamesAndValues as $attrName => $attrValue) {
            $this->dto->setHtmlAttribute($attrName, $attrValue);
        }

        return $this;
    }

    public function package(string $packageName): AssetInterface
    {
        $this->dto->setPackageName($packageName);

        return $this;
    }

    public function ignoreOnDetail(): AssetInterface
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(CrudInterface::PAGE_DETAIL);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreOnForm(): AssetInterface
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(CrudInterface::PAGE_NEW);
        $loadedOn->delete(CrudInterface::PAGE_EDIT);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreWhenCreating(): AssetInterface
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(CrudInterface::PAGE_NEW);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreWhenUpdating(): AssetInterface
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(CrudInterface::PAGE_EDIT);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function ignoreOnIndex(): AssetInterface
    {
        $loadedOn = $this->dto->getLoadedOn();
        $loadedOn->delete(CrudInterface::PAGE_INDEX);

        $this->dto->setLoadedOn($loadedOn);

        return $this;
    }

    public function onlyOnDetail(): AssetInterface
    {
        $this->dto->setLoadedOn(KeyValueStore::new([CrudInterface::PAGE_DETAIL => CrudInterface::PAGE_DETAIL]));

        return $this;
    }

    public function onlyOnForms(): AssetInterface
    {
        $this->dto->setLoadedOn(KeyValueStore::new([
            CrudInterface::PAGE_NEW => CrudInterface::PAGE_NEW,
            CrudInterface::PAGE_EDIT => CrudInterface::PAGE_EDIT,
        ]));

        return $this;
    }

    public function onlyOnIndex(): AssetInterface
    {
        $this->dto->setLoadedOn(KeyValueStore::new([CrudInterface::PAGE_INDEX => CrudInterface::PAGE_INDEX]));

        return $this;
    }

    public function onlyWhenCreating(): AssetInterface
    {
        $this->dto->setLoadedOn(KeyValueStore::new([CrudInterface::PAGE_NEW => CrudInterface::PAGE_NEW]));

        return $this;
    }

    public function onlyWhenUpdating(): AssetInterface
    {
        $this->dto->setLoadedOn(KeyValueStore::new([CrudInterface::PAGE_EDIT => CrudInterface::PAGE_EDIT]));

        return $this;
    }

    public function getAsDto(): AssetDtoInterface
    {
        return $this->dto;
    }
}
