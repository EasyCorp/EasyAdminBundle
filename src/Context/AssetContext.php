<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

final class AssetContext
{
    private $cssFiles;
    private $jsFiles;
    private $headContents;
    private $bodyContents;

    public function __construct(array $cssFiles, array $jsFiles, array $headContents, array $bodyContents)
    {
        $this->cssFiles = $cssFiles;
        $this->jsFiles = $jsFiles;
        $this->headContents = $headContents;
        $this->bodyContents = $bodyContents;
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

    public function mergeWith(AssetContext $assets): self
    {
        $this->cssFiles = array_unique(array_merge($this->cssFiles, $assets->getCssFiles()));
        $this->jsFiles = array_unique(array_merge($this->jsFiles, $assets->getJsFiles()));
        $this->headContents = array_unique(array_merge($this->headContents, $assets->getHeadContents()));
        $this->bodyContents = array_unique(array_merge($this->bodyContents, $assets->getBodyContents()));

        return $this;
    }
}
