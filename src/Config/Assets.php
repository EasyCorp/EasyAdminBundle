<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Assets
{
    /** @var AssetsDto */
    private $dto;

    private function __construct(AssetsDto $assetsDto)
    {
        $this->dto = $assetsDto;
    }

    public static function new(): self
    {
        $dto = new AssetsDto();

        return new self($dto);
    }

    /**
     * @param string|Asset $entryNameOrAsset
     */
    public function addWebpackEncoreEntry($entryNameOrAsset): self
    {
        if (!class_exists('Symfony\\WebpackEncoreBundle\\WebpackEncoreBundle')) {
            throw new \RuntimeException(sprintf('You are trying to add a Webpack Encore entry called "%s" but WebpackEncoreBundle is not installed in your project. Try running "composer require symfony/webpack-encore-bundle"', $entryNameOrAsset));
        }

        if (!\is_string($entryNameOrAsset) && !($entryNameOrAsset instanceof Asset)) {
            throw new \RuntimeException(sprintf('The argument passed to %s() can only be a string or a object of type "%s".', __METHOD__, Asset::class));
        }

        if (\is_string($entryNameOrAsset)) {
            $this->dto->addWebpackEncoreAsset(new AssetDto($entryNameOrAsset));
        } else {
            $this->dto->addWebpackEncoreAsset($entryNameOrAsset->getAsDto());
        }

        return $this;
    }

    /**
     * @param string|Asset $pathOrAsset
     */
    public function addCssFile($pathOrAsset): self
    {
        if (!\is_string($pathOrAsset) && !($pathOrAsset instanceof Asset)) {
            throw new \RuntimeException(sprintf('The argument passed to %s() can only be a string or a object of type "%s".', __METHOD__, Asset::class));
        }

        if (\is_string($pathOrAsset)) {
            $this->dto->addCssAsset(new AssetDto($pathOrAsset));
        } else {
            $this->dto->addCssAsset($pathOrAsset->getAsDto());
        }

        return $this;
    }

    /**
     * @param string|Asset $pathOrAsset
     */
    public function addJsFile($pathOrAsset): self
    {
        if (!\is_string($pathOrAsset) && !($pathOrAsset instanceof Asset)) {
            throw new \RuntimeException(sprintf('The argument passed to %s() can only be a string or a object of type "%s".', __METHOD__, Asset::class));
        }

        if (\is_string($pathOrAsset)) {
            $this->dto->addJsAsset(new AssetDto($pathOrAsset));
        } else {
            $this->dto->addJsAsset($pathOrAsset->getAsDto());
        }

        return $this;
    }

    public function addHtmlContentToHead(string $htmlContent): self
    {
        $this->dto->addHtmlContentToHead($htmlContent);

        return $this;
    }

    public function addHtmlContentToBody(string $htmlContent): self
    {
        $this->dto->addHtmlContentToBody($htmlContent);

        return $this;
    }

    public function getAsDto(): AssetsDto
    {
        return $this->dto;
    }
}
