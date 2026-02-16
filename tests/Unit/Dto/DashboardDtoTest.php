<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use PHPUnit\Framework\TestCase;

class DashboardDtoTest extends TestCase
{
    public function testGetLocalesFromList(): void
    {
        $dashboard = Dashboard::new();
        $dashboard->setLocales(['en', 'pl']);

        $dashboardDto = $dashboard->getAsDto();
        [$locale1, $locale2] = $dashboardDto->getLocales();

        $this->assertSame('en', $locale1->getLocale());
        $this->assertSame('English', $locale1->getName());
        $this->assertSame('pl', $locale2->getLocale());
        $this->assertSame('polski', $locale2->getName());
    }

    public function testUseEntityTranslationsDefaultsToFalse(): void
    {
        $dashboard = Dashboard::new();
        $dashboardDto = $dashboard->getAsDto();

        $this->assertFalse($dashboardDto->isUseEntityTranslations());
    }

    public function testUseEntityTranslationsCanBeEnabled(): void
    {
        $dashboard = Dashboard::new();
        $dashboard->useEntityTranslations();

        $dashboardDto = $dashboard->getAsDto();

        $this->assertTrue($dashboardDto->isUseEntityTranslations());
    }

    public function testUseEntityTranslationsCanBeExplicitlyEnabled(): void
    {
        $dashboard = Dashboard::new();
        $dashboard->useEntityTranslations(true);

        $dashboardDto = $dashboard->getAsDto();

        $this->assertTrue($dashboardDto->isUseEntityTranslations());
    }

    public function testUseEntityTranslationsCanBeDisabled(): void
    {
        $dashboard = Dashboard::new();
        $dashboard->useEntityTranslations(true);
        $dashboard->useEntityTranslations(false);

        $dashboardDto = $dashboard->getAsDto();

        $this->assertFalse($dashboardDto->isUseEntityTranslations());
    }

    public function testUseEntityTranslationsMethodIsFluent(): void
    {
        $dashboard = Dashboard::new();
        $result = $dashboard->useEntityTranslations();

        $this->assertSame($dashboard, $result);
    }
}
