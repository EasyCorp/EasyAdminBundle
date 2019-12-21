<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class AssetDto
{
    private $cssFiles;
    private $jsFiles;
    private $headContents;
    private $bodyContents;

    public function __construct(array $cssFiles = [], array $jsFiles = [], array $headContents = [], array $bodyContents = [])
    {
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

    public function mergeWith(AssetDto $assetDto): self
    {
        $this->cssFiles = array_unique(array_merge($this->cssFiles, $assetDto->getCssFiles()));
        $this->jsFiles = array_unique(array_merge($this->jsFiles, $assetDto->getJsFiles()));
        $this->headContents = array_unique(array_merge($this->headContents, $assetDto->getHeadContents()));
        $this->bodyContents = array_unique(array_merge($this->bodyContents, $assetDto->getBodyContents()));

        return $this;
    }
}
