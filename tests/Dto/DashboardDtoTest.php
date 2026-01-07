<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Dto;

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
}
