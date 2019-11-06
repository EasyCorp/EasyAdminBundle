<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

final class AssetCollection
{
    private $cssAssets = [];
    private $jsAssets = [];
    private $headContents = [];
    private $bodyContents = [];

    public function __construct(AssetConfig ...$assetConfigs)
    {
        $cssAssets = $jsAssets = $headContents = $bodyContents = [];
        foreach ($assetConfigs as $assetConfig) {
            $cssAssets = array_merge($cssAssets, $assetConfig->getCssAssets());
            $jsAssets = array_merge($jsAssets, $assetConfig->getJsAssets());
            $headContents = array_merge($headContents, $assetConfig->getHeadContents());
            $bodyContents = array_merge($bodyContents, $assetConfig->getBodyContents());
        }

        $this->cssAssets = array_unique($cssAssets);
        $this->jsAssets = array_unique($jsAssets);
        $this->headContents = array_unique($headContents);
        $this->bodyContents = array_unique($bodyContents);
    }

    public function getCssAssets(): array
    {
        return $this->cssAssets;
    }

    public function getJsAssets(): array
    {
        return $this->jsAssets;
    }

    public function getHeadContents(): array
    {
        return $this->headContents;
    }

    public function getBodyContents(): array
    {
        return $this->bodyContents;
    }
}
