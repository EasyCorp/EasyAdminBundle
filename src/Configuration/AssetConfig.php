<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\AssetContext;

final class AssetConfig
{
    private $cssFiles = [];
    private $jsFiles = [];
    private $headContents = [];
    private $bodyContents = [];

    public static function new(): self
    {
        return new self();
    }

    public function addCssFile(string $path): self
    {
        $this->cssFiles[] = $path;

        return $this;
    }

    public function addJsFile(string $path): self
    {
        $this->jsFiles[] = $path;

        return $this;
    }

    public function addHtmlContentToHead(string $htmlContent): self
    {
        $this->headContents[] = $htmlContent;

        return $this;
    }

    public function addHtmlContentToBody(string $htmlContent): self
    {
        $this->bodyContents[] = $htmlContent;

        return $this;
    }

    public function getAsValueObject(): AssetContext
    {
        return new AssetContext($this->cssFiles, $this->jsFiles, $this->headContents, $this->bodyContents);
    }
}
