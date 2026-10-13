<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ComponentDefaultsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Provider\ComponentDefaultsProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\AdminControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ComponentDefaultsProviderTest extends TestCase
{
    public function testWithoutAdminContext(): void
    {
        $provider = new ComponentDefaultsProvider(new AdminContextProvider(new RequestStack()));

        $this->assertNull($provider->getDefaults()->getBadgeVariant());
    }

    public function testUsesTheDefaultsOfTheCrudController(): void
    {
        $crudDto = new CrudDto();
        $crudDto->setComponentDefaults(new ComponentDefaultsDto(BadgeVariant::Solid));
        $dashboardDto = new DashboardDto();
        $dashboardDto->setComponentDefaults(new ComponentDefaultsDto(BadgeVariant::Dot));

        $provider = $this->createProvider(AdminContext::forTesting(
            crudContext: CrudContext::forTesting($crudDto),
            dashboardContext: DashboardContext::forTesting($dashboardDto),
        ));

        $this->assertSame(BadgeVariant::Solid, $provider->getDefaults()->getBadgeVariant());
    }

    public function testUsesTheDefaultsOfTheDashboardWhenThereIsNoCrudController(): void
    {
        $dashboardDto = new DashboardDto();
        $dashboardDto->setComponentDefaults(new ComponentDefaultsDto(BadgeVariant::Dot));

        // the same context that AdminContextFactory creates for pages without a CRUD controller
        $provider = $this->createProvider(AdminContext::forTesting(
            crudContext: new CrudContext(null, null, null, new AdminControllerRegistry(new ArrayAdapter())),
            dashboardContext: DashboardContext::forTesting($dashboardDto),
        ));

        $this->assertSame(BadgeVariant::Dot, $provider->getDefaults()->getBadgeVariant());
    }

    public function testCustomAdminContextWithoutDashboardComponentDefaults(): void
    {
        $adminContext = $this->createStub(AdminContextInterface::class);
        $adminContextProvider = $this->createStub(AdminContextProviderInterface::class);
        $adminContextProvider->method('getContext')->willReturn($adminContext);

        $provider = new ComponentDefaultsProvider($adminContextProvider);

        $this->assertNull($provider->getDefaults()->getBadgeVariant());
    }

    private function createProvider(AdminContext $adminContext): ComponentDefaultsProvider
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(attributes: [EA::CONTEXT_REQUEST_ATTRIBUTE => $adminContext]));

        return new ComponentDefaultsProvider(new AdminContextProvider($requestStack));
    }
}
