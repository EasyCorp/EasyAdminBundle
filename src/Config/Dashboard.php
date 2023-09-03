<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDtoInterface;

final class Dashboard implements DashboardInterface
{
    private DashboardDto $dto;

    private function __construct(DashboardDtoInterface $dashboardDto)
    {
        $this->dto = $dashboardDto;
    }

    public static function new(): DashboardInterface
    {
        $dto = new DashboardDto();

        return new self($dto);
    }

    public function setFaviconPath(string $path): DashboardInterface
    {
        $this->dto->setFaviconPath($path);

        return $this;
    }

    public function setTitle(string $title): DashboardInterface
    {
        $this->dto->setTitle($title);

        return $this;
    }

    public function setTranslationDomain(string $translationDomain): DashboardInterface
    {
        $this->dto->setTranslationDomain($translationDomain);

        return $this;
    }

    public function setTextDirection(string $direction): DashboardInterface
    {
        if (!\in_array($direction, [TextDirection::LTR, TextDirection::RTL], true)) {
            throw new \InvalidArgumentException(sprintf('The "%s" value given to the textDirection option is not valid. It can only be "%s" or "%s"', $direction, TextDirection::LTR, TextDirection::RTL));
        }

        $this->dto->setTextDirection($direction);

        return $this;
    }

    public function renderContentMaximized(bool $maximized = true): DashboardInterface
    {
        $this->dto->setContentWidth($maximized ? Crud::LAYOUT_CONTENT_FULL : Crud::LAYOUT_CONTENT_DEFAULT);

        return $this;
    }

    public function renderSidebarMinimized(bool $minimized = true): DashboardInterface
    {
        $this->dto->setSidebarWidth($minimized ? Crud::LAYOUT_SIDEBAR_COMPACT : Crud::LAYOUT_SIDEBAR_DEFAULT);

        return $this;
    }

    public function disableUrlSignatures(bool $disableSignatures = true): DashboardInterface
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.1.0',
            'EasyAdmin URLs no longer include signatures because they don\'t provide any additional security. You can stop calling the "%s" method to disable them. This method will be removed in future EasyAdmin versions.',
            __METHOD__,
        );

        $this->dto->setSignedUrls(!$disableSignatures);

        return $this;
    }

    public function generateRelativeUrls(bool $relativeUrls = true): DashboardInterface
    {
        $this->dto->setAbsoluteUrls(!$relativeUrls);

        return $this;
    }

    public function disableDarkMode(bool $disableDarkMode = true): DashboardInterface
    {
        $this->dto->setEnableDarkMode(!$disableDarkMode);

        return $this;
    }

    public function setLocales(array $locales): DashboardInterface
    {
        $localeDtos = [];
        foreach ($locales as $key => $value) {
            $locale = match (true) {
                $value instanceof Locale => $value,
                \is_string($key) => Locale::new($key, (string) $value),
                default => Locale::new((string) $value),
            };

            $localeDtos[] = $locale->getAsDto();
        }

        $this->dto->setLocales($localeDtos);

        return $this;
    }

    public function getAsDto(): DashboardDtoInterface
    {
        return $this->dto;
    }
}
