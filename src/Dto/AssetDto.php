<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class AssetDto
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
}
