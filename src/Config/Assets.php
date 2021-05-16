<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Assets
{
    /** @var AssetsDto */
    private $dto;

    private function __construct(AssetsDto $assetsDto)
    {
        $this->dto = $assetsDto;
    }

    public static function new(): self
    {
        $dto = new AssetsDto();

        return new self($dto);
    }

    public function addWebpackEncoreEntry(string $entryName): self
    {
        if (!class_exists('Symfony\\WebpackEncoreBundle\\Twig\\EntryFilesTwigExtension')) {
            throw new \RuntimeException('You are trying to add Webpack Encore entries in the backend but Webpack Encore is not installed in your project. Try running "composer req symfony/webpack-encore-bundle"');
        }

        $this->dto->addWebpackEncoreEntry($entryName);

        return $this;
    }

    public function addCssFile(string $path): self
    {
        $this->dto->addCssFile($path);

        return $this;
    }

    public function addJsFile(string $path): self
    {
        $this->dto->addJsFile($path);

        return $this;
    }

    public function addHtmlContentToHead(string $htmlContent): self
    {
        $this->dto->addHtmlContentToHead($htmlContent);

        return $this;
    }

    public function addHtmlContentToBody(string $htmlContent): self
    {
        $this->dto->addHtmlContentToBody($htmlContent);

        return $this;
    }

    public function getAsDto(): AssetsDto
    {
        return $this->dto;
    }
}
