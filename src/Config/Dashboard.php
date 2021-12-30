<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Dashboard
{
    /** @var DashboardDto */
    private $dto;

    private function __construct(DashboardDto $dashboardDto)
    {
        $this->dto = $dashboardDto;
    }

    public static function new(): self
    {
        $dto = new DashboardDto();

        return new self($dto);
    }

    public function setFaviconPath(string $path): self
    {
        $this->dto->setFaviconPath($path);

        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->dto->setTitle($title);

        return $this;
    }

    public function setTranslationDomain(string $translationDomain): self
    {
        $this->dto->setTranslationDomain($translationDomain);

        return $this;
    }

    public function setTextDirection(string $direction): self
    {
        if (!\in_array($direction, [TextDirection::LTR, TextDirection::RTL], true)) {
            throw new \InvalidArgumentException(sprintf('The "%s" value given to the textDirection option is not valid. It can only be "%s" or "%s"', $direction, TextDirection::LTR, TextDirection::RTL));
        }

        $this->dto->setTextDirection($direction);

        return $this;
    }

    public function renderContentMaximized(bool $maximized = true): self
    {
        $this->dto->setContentWidth($maximized ? Crud::LAYOUT_CONTENT_FULL : Crud::LAYOUT_CONTENT_DEFAULT);

        return $this;
    }

    public function renderSidebarMinimized(bool $minimized = true): self
    {
        $this->dto->setSidebarWidth($minimized ? Crud::LAYOUT_SIDEBAR_COMPACT : Crud::LAYOUT_SIDEBAR_DEFAULT);

        return $this;
    }

    public function disableUrlSignatures(bool $disableSignatures = true): self
    {
        $this->dto->setSignedUrls(!$disableSignatures);

        return $this;
    }

    public function generateRelativeUrls(bool $relativeUrls = true): self
    {
        $this->dto->setAbsoluteUrls(!$relativeUrls);

        return $this;
    }

    public function getAsDto(): DashboardDto
    {
        return $this->dto;
    }
}
