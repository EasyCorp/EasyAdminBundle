<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Asset
{
    /** @var AssetDto */
    private $dto;

    private function __construct(AssetDto $assetDto)
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
    public static function new(string $value): self
    {
        $isWebpackEncoreEntry = !u($value)->endsWith('.css') && !u($value)->endsWith('.js');
        if ($isWebpackEncoreEntry && !class_exists('Symfony\\WebpackEncoreBundle\\WebpackEncoreBundle')) {
            throw new \RuntimeException(sprintf('You are trying to add a Webpack Encore entry called "%s" but WebpackEncoreBundle is not installed in your project. Try running "composer require symfony/webpack-encore-bundle"', $value));
        }

        $dto = new AssetDto($value);

        return new self($dto);
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
        if (!class_exists('Symfony\\Component\\WebLink\Link')) {
            throw new \RuntimeException(sprintf('You are trying to configure the "nopush" preload attribute of an asset called "%s" but WebLink component is not installed in your project. Try running "composer require symfony/web-link"', $this->dto->getValue()));
        }

        $this->dto->setNopush($nopush);

        return $this;
    }

    public function webpackPackageName(string $packageName = null): self
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

    public function getAsDto(): AssetDto
    {
        return $this->dto;
    }
}
