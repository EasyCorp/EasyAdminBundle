<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AssetsDto
{
    private $cssFiles = [];
    private $jsFiles = [];
    private $headContents = [];
    private $bodyContents = [];

    public function __construct()
    {
    }

    public function addCssFile(string $path): void
    {
        if (\in_array($path, $this->cssFiles, true)) {
            return;
        }

        $this->cssFiles[] = $path;
    }

    public function addJsFile(string $path): void
    {
        if (\in_array($path, $this->jsFiles, true)) {
            return;
        }

        $this->jsFiles[] = $path;
    }

    public function addHtmlContentToHead(string $htmlContent): void
    {
        if (\in_array($htmlContent, $this->headContents, true)) {
            return;
        }

        $this->headContents[] = $htmlContent;
    }

    public function addHtmlContentToBody(string $htmlContent): void
    {
        if (\in_array($htmlContent, $this->bodyContents, true)) {
            return;
        }

        $this->bodyContents[] = $htmlContent;
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

    public function mergeWith(self $assetDto): self
    {
        $this->cssFiles = array_unique(array_merge($this->cssFiles, $assetDto->getCssFiles()));
        $this->jsFiles = array_unique(array_merge($this->jsFiles, $assetDto->getJsFiles()));
        $this->headContents = array_unique(array_merge($this->headContents, $assetDto->getHeadContents()));
        $this->bodyContents = array_unique(array_merge($this->bodyContents, $assetDto->getBodyContents()));

        return $this;
    }
}
