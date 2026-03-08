<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory\MenuFactoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AdminContextTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testGetReferrerEmptyString()
    {
        $target = new AdminContext(
            new Request(query: [EA::REFERRER => '']),
            null,
            new I18nDto('en', 'ltr', 'en', []),
            new CrudControllerRegistry([], [], [], []),
            new DashboardDto(),
            $this->createMock(DashboardControllerInterface::class),
            new AssetsDto(),
            null,
            null,
            null,
            $this->createMock(MenuFactoryInterface::class),
            TemplateRegistry::new(),
        );

        self::assertNull($target->getReferrer());
    }
}
