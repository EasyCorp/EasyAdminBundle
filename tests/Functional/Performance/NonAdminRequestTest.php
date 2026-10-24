<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Performance;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\AdminContextResolver;
use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\BatchActionDtoResolver;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\AdminRouterSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ActionFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;
use EasyCorp\Bundle\EasyAdminBundle\Registry\AdminControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use Symfony\Component\DependencyInjection\Container;

/**
 * The event dispatcher instantiates all the listeners of an event the first time it
 * dispatches it, and the argument resolver instantiates the value resolvers while it
 * looks for one that resolves each controller argument. So the listeners and value
 * resolvers of EasyAdmin are built on requests that are not related to the backend.
 */
class NonAdminRequestTest extends AbstractCrudTestCase
{
    private const ADMIN_SERVICES = [
        AdminContextFactory::class,
        ControllerFactory::class,
        AdminRouteGenerator::class,
        AdminControllerRegistry::class,
        MenuFactory::class,
        EntityFactory::class,
        ActionFactory::class,
        'cache.easyadmin',
    ];

    public function testNonAdminRequestDoesNotBuildAdminServices(): void
    {
        $this->requestWithFreshContainer('/non-admin-page');

        $this->assertResponseIsSuccessful();
        $instantiatedServiceIds = $this->getInstantiatedServiceIds();
        // these services run on every request, so they must be cheap to build
        $this->assertContains(AdminRouterSubscriber::class, $instantiatedServiceIds);
        $this->assertContains(AdminContextResolver::class, $instantiatedServiceIds);
        $this->assertContains(BatchActionDtoResolver::class, $instantiatedServiceIds);
        foreach (self::ADMIN_SERVICES as $serviceId) {
            $this->assertNotContains($serviceId, $instantiatedServiceIds);
        }
    }

    public function testAdminRequestBuildsAdminServices(): void
    {
        $this->requestWithFreshContainer($this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $instantiatedServiceIds = $this->getInstantiatedServiceIds();
        foreach (self::ADMIN_SERVICES as $serviceId) {
            $this->assertContains($serviceId, $instantiatedServiceIds);
        }
    }

    protected function getControllerFqcn(): string
    {
        return BlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    /**
     * The first request uses the container that setUp() already used to build the
     * URL generator, and it also dumps the router cache (which needs the admin route
     * generator). The client reboots the kernel before the second request, so that
     * one starts with a new container, like a request on a warm production cache.
     */
    private function requestWithFreshContainer(string $url): void
    {
        $this->client->request('GET', $url);
        $this->client->request('GET', $url);
    }

    /**
     * @return list<string>
     */
    private function getInstantiatedServiceIds(): array
    {
        $container = $this->client->getKernel()->getContainer();

        return array_merge(
            array_keys((new \ReflectionProperty(Container::class, 'services'))->getValue($container)),
            array_keys((new \ReflectionProperty(Container::class, 'privates'))->getValue($container)),
        );
    }
}
