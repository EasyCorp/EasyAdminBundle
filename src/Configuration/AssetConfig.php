<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

final class AssetConfig
{
    private $cssAssets = [];
    private $jsAssets = [];
    private $headContents = [];
    private $bodyContents = [];

    public static function new(): self
    {
        return new self();
    }

    public function addCss(string $path): self
    {
        $this->cssAssets[] = $path;

        return $this;
    }

    public function addJs(string $path): self
    {
        $this->jsAssets[] = $path;

        return $this;
    }

    public function addToHead(string $htmlContent): self
    {
        $this->headContents[] = $htmlContent;

        return $this;
    }

    public function addToBody(string $htmlContent): self
    {
        $this->bodyContents[] = $htmlContent;

        return $this;
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
