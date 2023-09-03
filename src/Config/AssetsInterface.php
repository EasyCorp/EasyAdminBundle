<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AssetsInterface
{
    public function addWebpackEncoreEntry(AssetInterface|string $entryNameOrAsset
    ): AssetsInterface;

    public function addCssFile(AssetInterface|string $pathOrAsset): AssetsInterface;

    public function addJsFile(AssetInterface|string $pathOrAsset): AssetsInterface;

    public function addHtmlContentToHead(string $htmlContent): AssetsInterface;

    public function addHtmlContentToBody(string $htmlContent): AssetsInterface;

    public function getAsDto(): AssetsDtoInterface;
}
