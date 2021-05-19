<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AssetsDto
{
    private $webpackEncoreAssets = [];
    private $cssAssets = [];
    private $jsAssets = [];
    private $headContents = [];
    private $bodyContents = [];

    public function __construct()
    {
    }

    public function addWebpackEncoreAsset(AssetDto $assetDto): void
    {
        if (\array_key_exists($entryName = $assetDto->getValue(), $this->webpackEncoreAssets)) {
            throw new \InvalidArgumentException(sprintf('The "%s" Webpack Encore entry has been added more than once via the addWebpackEncoreEntry() method, but each entry can only be added once (to not overwrite its configuration).', $entryName));
        }

        $this->webpackEncoreAssets[$entryName] = $assetDto;
    }

    public function addCssAsset(AssetDto $assetDto): void
    {
        if (\array_key_exists($cssPath = $assetDto->getValue(), $this->cssAssets)) {
            throw new \InvalidArgumentException(sprintf('The "%s" CSS file has been added more than once via the addCssFile() method, but each asset can only be added once (to not overwrite its configuration).', $cssPath));
        }

        $this->cssAssets[$cssPath] = $assetDto;
    }

    public function addJsAsset(AssetDto $assetDto): void
    {
        if (\array_key_exists($jsPath = $assetDto->getValue(), $this->jsAssets)) {
            throw new \InvalidArgumentException(sprintf('The "%s" JS file has been added more than once via the addJsFile() method, but each asset can only be added once (to not overwrite its configuration).', $jsPath));
        }

        $this->jsAssets[$jsPath] = $assetDto;
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

    /**
     * @return AssetDto[]
     */
    public function getWebpackEncoreAssets(): array
    {
        return $this->webpackEncoreAssets;
    }

    /**
     * @return AssetDto[]
     */
    public function getCssAssets(): array
    {
        return $this->cssAssets;
    }

    /**
     * @return AssetDto[]
     */
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

    public function mergeWith(self $assetsDto): self
    {
        $this->webpackEncoreAssets = array_merge($this->webpackEncoreAssets, $assetsDto->getWebpackEncoreAssets());
        $this->cssAssets = array_merge($this->cssAssets, $assetsDto->getCssAssets());
        $this->jsAssets = array_merge($this->jsAssets, $assetsDto->getJsAssets());
        $this->headContents = array_merge($this->headContents, $assetsDto->getHeadContents());
        $this->bodyContents = array_merge($this->bodyContents, $assetsDto->getBodyContents());

        return $this;
    }
}
