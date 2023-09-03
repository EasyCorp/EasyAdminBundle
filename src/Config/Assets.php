<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDtoInterface;

final class Assets implements AssetsInterface
{
    private AssetsDtoInterface $dto;

    private function __construct(AssetsDtoInterface $assetsDto)
    {
        $this->dto = $assetsDto;
    }

    public static function new(): AssetsInterface
    {
        $dto = new AssetsDto();

        return new self($dto);
    }

    public function addWebpackEncoreEntry(AssetInterface|string $entryNameOrAsset): AssetsInterface
    {
        if (!class_exists('Symfony\\WebpackEncoreBundle\\WebpackEncoreBundle')) {
            throw new \RuntimeException(
                sprintf(
                    'You are trying to add a Webpack Encore entry called "%s" but WebpackEncoreBundle is not installed in your project. Try running "composer require symfony/webpack-encore-bundle"',
                    $entryNameOrAsset
                )
            );
        }

        if (\is_string($entryNameOrAsset)) {
            $this->dto->addWebpackEncoreAsset(new AssetDto($entryNameOrAsset));
        } else {
            $this->dto->addWebpackEncoreAsset($entryNameOrAsset->getAsDto());
        }

        return $this;
    }

    public function addCssFile(AssetInterface|string $pathOrAsset): AssetsInterface
    {
        if (\is_string($pathOrAsset)) {
            $this->dto->addCssAsset(new AssetDto($pathOrAsset));
        } else {
            $this->dto->addCssAsset($pathOrAsset->getAsDto());
        }

        return $this;
    }

    public function addJsFile(AssetInterface|string $pathOrAsset): AssetsInterface
    {
        if (\is_string($pathOrAsset)) {
            $this->dto->addJsAsset(new AssetDto($pathOrAsset));
        } else {
            $this->dto->addJsAsset($pathOrAsset->getAsDto());
        }

        return $this;
    }

    public function addHtmlContentToHead(string $htmlContent): AssetsInterface
    {
        $this->dto->addHtmlContentToHead($htmlContent);

        return $this;
    }

    public function addHtmlContentToBody(string $htmlContent): AssetsInterface
    {
        $this->dto->addHtmlContentToBody($htmlContent);

        return $this;
    }

    public function getAsDto(): AssetsDtoInterface
    {
        return $this->dto;
    }
}
