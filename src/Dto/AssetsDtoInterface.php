<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AssetsDtoInterface
{
    public function addWebpackEncoreAsset(AssetDtoInterface $assetDto): void;

    public function addCssAsset(AssetDtoInterface $assetDto): void;

    public function addJsAsset(AssetDtoInterface $assetDto): void;

    public function addHtmlContentToHead(string $htmlContent): void;

    public function addHtmlContentToBody(string $htmlContent): void;

    public function getDefaultAssetPackageName(): string;

    /**
     * @return AssetDtoInterface[]
     */
    public function getWebpackEncoreAssets(): array;

    /**
     * @return AssetDtoInterface[]
     */
    public function getCssAssets(): array;

    /**
     * @return AssetDtoInterface[]
     */
    public function getJsAssets(): array;

    public function getHeadContents(): array;

    public function getBodyContents(): array;

    public function loadedOn(?string $pageName): AssetsDtoInterface;

    public function mergeWith(self $assetsDto): AssetsDtoInterface;
}
