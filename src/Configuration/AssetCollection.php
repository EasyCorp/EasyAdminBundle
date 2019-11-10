<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\AssetContext;

final class AssetCollection
{
    private $cssFiles = [];
    private $jsFiles = [];
    private $headContents = [];
    private $bodyContents = [];

    public function __construct(AssetContext ...$assetContexts)
    {
        $cssFiles = $jsFiles = $headContents = $bodyContents = [];
        foreach ($assetContexts as $assetContext) {
            $cssFiles = array_merge($cssFiles, $assetContext->getCssFiles());
            $jsFiles = array_merge($jsFiles, $assetContext->getJsFiles());
            $headContents = array_merge($headContents, $assetContext->getHeadContents());
            $bodyContents = array_merge($bodyContents, $assetContext->getBodyContents());
        }

        $this->cssFiles = array_unique($cssFiles);
        $this->jsFiles = array_unique($jsFiles);
        $this->headContents = array_unique($headContents);
        $this->bodyContents = array_unique($bodyContents);
    }

    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    public function getJsFiles(): array
    {
        return $this->jsFiles;
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
