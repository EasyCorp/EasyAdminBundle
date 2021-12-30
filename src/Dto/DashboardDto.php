<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DashboardDto
{
    private $routeName;
    private $faviconPath;
    private $title;
    private $translationDomain;
    private $textDirection;
    private $contentWidth;
    private $sidebarWidth;
    private $signedUrls;
    private $absoluteUrls;

    public function __construct()
    {
        $this->faviconPath = 'favicon.ico';
        $this->title = 'EasyAdmin';
        $this->translationDomain = 'messages';
        $this->contentWidth = Crud::LAYOUT_CONTENT_DEFAULT;
        $this->sidebarWidth = Crud::LAYOUT_SIDEBAR_DEFAULT;
        $this->signedUrls = true;
        $this->absoluteUrls = true;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName($routeName): void
    {
        $this->routeName = $routeName;
    }

    public function getFaviconPath(): string
    {
        return $this->faviconPath;
    }

    public function setFaviconPath(string $faviconPath): void
    {
        $this->faviconPath = $faviconPath;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function setTranslationDomain(string $translationDomain): void
    {
        $this->translationDomain = $translationDomain;
    }

    public function getTextDirection(): ?string
    {
        return $this->textDirection;
    }

    public function setTextDirection($textDirection): void
    {
        $this->textDirection = $textDirection;
    }

    public function getContentWidth(): string
    {
        return $this->contentWidth;
    }

    public function setContentWidth(string $contentWidth): void
    {
        $this->contentWidth = $contentWidth;
    }

    public function getSidebarWidth(): string
    {
        return $this->sidebarWidth;
    }

    public function setSidebarWidth(string $sidebarWidth): void
    {
        $this->sidebarWidth = $sidebarWidth;
    }

    public function getSignedUrls(): bool
    {
        return $this->signedUrls;
    }

    public function setSignedUrls(bool $signedUrls): self
    {
        $this->signedUrls = $signedUrls;

        return $this;
    }

    public function getAbsoluteUrls(): bool
    {
        return $this->absoluteUrls;
    }

    public function setAbsoluteUrls(bool $absoluteUrls): self
    {
        $this->absoluteUrls = $absoluteUrls;

        return $this;
    }
}
