<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class DashboardDto
{
    use PropertyModifierTrait;

    private $routeName;
    private $faviconPath;
    private $title;
    private $translationDomain;
    private $textDirection;

    public function __construct(string $faviconPath, string $title, string $translationDomain, ?string $textDirection)
    {
        $this->faviconPath = $faviconPath;
        $this->title = $title;
        $this->translationDomain = $translationDomain;
        $this->textDirection = $textDirection;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getFaviconPath(): string
    {
        return $this->faviconPath;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function getTextDirection(): ?string
    {
        return $this->textDirection;
    }
}
