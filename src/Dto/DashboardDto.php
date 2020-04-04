<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

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

    public function __construct()
    {
        $this->faviconPath = 'favicon.ico';
        $this->title = 'EasyAdmin';
        $this->translationDomain = 'messages';
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
}
