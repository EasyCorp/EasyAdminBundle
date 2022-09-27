<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use PHPUnit\Framework\TestCase;

class DashboardDtoTest extends TestCase
{
    public function testGetLocalesFromList()
    {
        $dashboardDto = new DashboardDto();

        $dashboardDto->setLocales(['en', 'pl']);

        $locales = $dashboardDto->getLocales();

        $this->assertSame('en', $locales[0]->getLocale());
        $this->assertSame('English', $locales[0]->getName());
        $this->assertSame('pl', $locales[1]->getLocale());
        $this->assertSame('polski', $locales[1]->getName());
    }
}
