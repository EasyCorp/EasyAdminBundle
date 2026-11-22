<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\ArrayWidgetContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\CallbackWidgetConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\DummyWidget;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\InMemoryLogger;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\OtherWidgetsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\WidgetsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetRenderer;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext as RoutingRequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ArrayLoader;

class WidgetRendererTest extends TestCase
{
    private const TEMPLATES = [
        'dummy.html.twig' => '{{ widget.name }}:{% for name, value in data %}{{ name }}={{ value }};{% endfor %}',
        'context.html.twig' => '{{ context.getAttribute("range") }}|{{ context.dashboardControllerFqcn }}|{{ context.widget.name }}',
        'custom_stat.html.twig' => 'custom stat:{{ data.value }}',
        '@EasyAdmin/widget/stat.html.twig' => 'stat:{{ data.value }}',
        '@EasyAdmin/widget/_error.html.twig' => 'error:{{ widget.name }}',
    ];

    private RequestStack $requestStack;
    private ArrayAdapter $cache;
    private InMemoryLogger $logger;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->cache = new ArrayAdapter();
        $this->logger = new InMemoryLogger();
    }

    public function testDataIsResolvedAndPassedToTheTemplate(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest();

        $widget = DummyWidget::new('Users')
            ->setName('users')
            ->setValue('total', 10)
            ->setValue('active', static fn (WidgetContext $context): string => 'active of '.$context->getWidget()->getName())
            ->setValue('function', 'phpinfo')
            ->getAsDto();

        $this->assertSame('users:total=10;active=active of users;function=phpinfo;', $renderer->renderBody($widget));
        $this->assertFalse($renderer->hasFailed($widget));
    }

    public function testContextIsPassedToTheTemplate(): void
    {
        $renderer = $this->createRenderer([new ArrayWidgetContextProvider(['range' => '30d'])]);
        $this->pushAdminRequest();

        $widget = DummyWidget::new()->setName('users')->setTemplatePath('context.html.twig')->getAsDto();

        $this->assertSame('30d|'.WidgetsDashboardController::class.'|users', $renderer->renderBody($widget));
    }

    public function testTemplateNameIsResolvedWithTheTemplateRegistry(): void
    {
        $renderer = $this->createRenderer();
        $widget = DummyWidget::new()->setName('users')->setTemplateName('widget/stat')->setValue('value', 7)->getAsDto();

        $this->pushAdminRequest();
        $this->assertSame('stat:7', $renderer->renderBody($widget));

        $templateRegistry = TemplateRegistry::new();
        $templateRegistry->setTemplate('widget/stat', 'custom_stat.html.twig');
        $this->pushAdminRequest(templateRegistry: $templateRegistry);
        $this->assertSame('custom stat:7', $renderer->renderBody($widget));
    }

    #[TestWith(['page/dashboard', '@EasyAdmin/page/dashboard.html.twig'])]
    #[TestWith(['widget/chart', '@EasyAdmin/widget/chart.html.twig'])]
    #[TestWith(['widget/list', '@EasyAdmin/widget/list.html.twig'])]
    #[TestWith(['widget/progress', '@EasyAdmin/widget/progress.html.twig'])]
    #[TestWith(['widget/stat', '@EasyAdmin/widget/stat.html.twig'])]
    #[TestWith(['widget/twig', '@EasyAdmin/widget/twig.html.twig'])]
    public function testWidgetTemplatesAreRegistered(string $templateName, string $templatePath): void
    {
        $this->assertSame($templatePath, TemplateRegistry::new()->get($templateName));
    }

    public function testWidgetWithoutTemplateThrowsInDebugMode(): void
    {
        $renderer = $this->createRenderer(debug: true);
        $this->pushAdminRequest();

        $widget = new WidgetDto();
        $widget->setWidgetFqcn(DummyWidget::class);
        $widget->setName('users');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "users" widget (of type "%s") doesn\'t define the template used to render its contents.', DummyWidget::class));

        $renderer->renderBody($widget);
    }

    public function testMissingTemplateThrowsInDebugMode(): void
    {
        $renderer = $this->createRenderer(debug: true);
        $this->pushAdminRequest();

        $this->expectException(LoaderError::class);

        $renderer->renderBody(DummyWidget::new()->setName('users')->setTemplatePath('missing.html.twig')->getAsDto());
    }

    public function testContextProvidersAreMergedAndTheLastOneWins(): void
    {
        $firstProvider = new ArrayWidgetContextProvider(['range' => '7d', 'tenant' => 'acme']);
        $secondProvider = new ArrayWidgetContextProvider(['range' => '30d']);
        $renderer = $this->createRenderer([$firstProvider, $secondProvider]);
        $request = $this->pushAdminRequest();

        $widget = DummyWidget::new()->setName('users')
            ->setValue('range', static fn (WidgetContext $context): string => $context->getAttribute('range'))
            ->setValue('tenant', static fn (WidgetContext $context): string => $context->getAttribute('tenant'))
            ->getAsDto();

        $this->assertSame('users:range=30d;tenant=acme;', $renderer->renderBody($widget));
        $this->assertSame('users:range=30d;tenant=acme;', $renderer->renderBody($widget));
        $this->assertSame(1, $firstProvider->calls, 'the providers are called once per request');

        $context = $renderer->createContext($widget);
        $this->assertSame($request, $context->getRequest());
        $this->assertSame($widget, $context->getWidget());
        $this->assertSame(['range' => '30d', 'tenant' => 'acme'], $context->getAttributes());
    }

    public function testCreateContextIncludesTheUser(): void
    {
        $user = new InMemoryUser('admin', null);
        $renderer = $this->createRenderer();
        $this->pushAdminRequest($user);

        $context = $renderer->createContext(new WidgetDto());

        $this->assertSame($user, $context->getUser());
        $this->assertSame(WidgetsDashboardController::class, $context->getDashboardControllerFqcn());
    }

    public function testRenderingOutsideEasyAdminThrows(): void
    {
        $renderer = $this->createRenderer();
        $this->requestStack->push(new Request());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Widgets can only be rendered in the pages served by EasyAdmin');

        $renderer->renderBody(DummyWidget::new()->setName('users')->getAsDto());
    }

    public function testSecondRenderOfACachedWidgetIsACacheHit(): void
    {
        $calls = 0;
        $renderer = $this->createRenderer();
        $widget = $this->createCountingWidget($calls)->setCache(60)->getAsDto();

        $this->pushAdminRequest();
        $this->assertSame('users:value=1;', $renderer->renderBody($widget));
        $this->assertSame('users:value=1;', $renderer->renderBody($widget));

        $this->pushAdminRequest();
        $this->assertSame('users:value=1;', $renderer->renderBody($widget));

        $this->assertSame(1, $calls);
    }

    public function testWidgetsWithoutCacheAreRenderedEachTime(): void
    {
        $calls = 0;
        $renderer = $this->createRenderer();
        $widget = $this->createCountingWidget($calls)->getAsDto();

        $this->pushAdminRequest();
        $renderer->renderBody($widget);
        $renderer->renderBody($widget);

        $this->assertSame(2, $calls);
        $this->assertSame([], $this->cache->getValues());
    }

    public function testEachUserHasTheirOwnCache(): void
    {
        $renderer = $this->createRenderer();
        $widget = $this->createUserWidget()->setCache(60)->getAsDto();

        $this->pushAdminRequest(new InMemoryUser('alice', null));
        $this->assertSame('users:user=alice;', $renderer->renderBody($widget));

        $this->pushAdminRequest(new InMemoryUser('bob', null));
        $this->assertSame('users:user=bob;', $renderer->renderBody($widget));

        $this->pushAdminRequest();
        $this->assertSame('users:user=anonymous;', $renderer->renderBody($widget));

        $this->pushAdminRequest(new InMemoryUser('alice', null));
        $this->assertSame('users:user=alice;', $renderer->renderBody($widget));

        $this->assertCount(3, $this->cache->getValues());
    }

    public function testSharedCacheIsTheSameForAllUsers(): void
    {
        $renderer = $this->createRenderer();
        $widget = $this->createUserWidget()->setCache(60, true)->getAsDto();

        $this->pushAdminRequest(new InMemoryUser('alice', null));
        $this->assertSame('users:user=alice;', $renderer->renderBody($widget));

        $this->pushAdminRequest(new InMemoryUser('bob', null));
        $this->assertSame('users:user=alice;', $renderer->renderBody($widget));

        $this->pushAdminRequest();
        $this->assertSame('users:user=alice;', $renderer->renderBody($widget));

        $this->assertCount(1, $this->cache->getValues());
    }

    public function testChangedContextAttributeIsACacheMiss(): void
    {
        $calls = 0;
        $provider = new ArrayWidgetContextProvider(['range' => '7d']);
        $renderer = $this->createRenderer([$provider]);
        $widget = $this->createCountingWidget($calls)->setCache(60)->getAsDto();

        $this->pushAdminRequest();
        $renderer->renderBody($widget);

        $provider->attributes = ['range' => '30d'];
        $this->pushAdminRequest();
        $renderer->renderBody($widget);

        $provider->attributes = ['range' => '7d'];
        $this->pushAdminRequest();
        $renderer->renderBody($widget);

        $this->assertSame(2, $calls);
    }

    public function testChangedLocaleIsACacheMiss(): void
    {
        $calls = 0;
        $renderer = $this->createRenderer();
        $widget = $this->createCountingWidget($calls)->setCache(60)->getAsDto();

        $this->pushAdminRequest(locale: 'en');
        $renderer->renderBody($widget);

        $this->pushAdminRequest(locale: 'es');
        $renderer->renderBody($widget);

        $this->assertSame(2, $calls);
    }

    public function testDifferentDashboardsDoNotShareTheCache(): void
    {
        $calls = 0;
        $renderer = $this->createRenderer();
        $widget = $this->createCountingWidget($calls)->setCache(60, true)->getAsDto();

        $this->pushAdminRequest(dashboardControllerFqcn: WidgetsDashboardController::class);
        $renderer->renderBody($widget);

        $this->pushAdminRequest(dashboardControllerFqcn: OtherWidgetsDashboardController::class);
        $renderer->renderBody($widget);

        $this->assertSame(2, $calls);
    }

    public function testDifferentWidgetsDoNotShareTheCache(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest();

        $users = DummyWidget::new()->setName('users')->setValue('value', 1)->setCache(60)->getAsDto();
        $orders = DummyWidget::new()->setName('orders')->setValue('value', 2)->setCache(60)->getAsDto();

        $this->assertSame('users:value=1;', $renderer->renderBody($users));
        $this->assertSame('orders:value=2;', $renderer->renderBody($orders));
    }

    public function testCacheTtlIsApplied(): void
    {
        if ((new \ReflectionMethod(ArrayAdapter::class, '__construct'))->getNumberOfParameters() < 5) {
            $this->markTestSkipped('This version of symfony/cache does not allow to set the clock of ArrayAdapter.');
        }

        $clock = new MockClock();
        $this->cache = new ArrayAdapter(clock: $clock);
        $calls = 0;
        $renderer = $this->createRenderer();
        $widget = $this->createCountingWidget($calls)->setCache(60)->getAsDto();

        $this->pushAdminRequest();
        $renderer->renderBody($widget);

        $clock->sleep(59);
        $renderer->renderBody($widget);
        $this->assertSame(1, $calls);

        $clock->sleep(2);
        $renderer->renderBody($widget);
        $this->assertSame(2, $calls);
    }

    public function testExceptionsAreThrownInDebugMode(): void
    {
        $renderer = $this->createRenderer(debug: true);
        $this->pushAdminRequest();

        $widget = DummyWidget::new()->setName('users')
            ->setValue('total', static fn (): never => throw new \RuntimeException('Database is down.'))
            ->getAsDto();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database is down.');

        $renderer->renderBody($widget);
    }

    public function testExceptionsAreLoggedOutsideDebugMode(): void
    {
        $exception = new \RuntimeException('Database is down.');
        $renderer = $this->createRenderer();
        $this->pushAdminRequest();

        $widget = DummyWidget::new()->setName('users')
            ->setValue('total', static fn (): never => throw $exception)
            ->getAsDto();

        $this->assertSame('error:users', $renderer->renderBody($widget));
        $this->assertTrue($renderer->hasFailed($widget));

        $this->assertCount(1, $this->logger->records);
        $record = $this->logger->records[0];
        $this->assertSame(LogLevel::ERROR, $record['level']);
        $this->assertSame('users', $record['context']['widget']);
        $this->assertSame(WidgetsDashboardController::class, $record['context']['dashboard']);
        $this->assertSame('Database is down.', $record['context']['message']);
        $this->assertSame($exception, $record['context']['exception']);
    }

    public function testFailuresAreNotCached(): void
    {
        $fail = true;
        $renderer = $this->createRenderer();
        $widget = DummyWidget::new()->setName('users')
            ->setValue('total', static function () use (&$fail): int {
                if ($fail) {
                    throw new \RuntimeException('Database is down.');
                }

                return 5;
            })
            ->setCache(60)
            ->getAsDto();

        $this->pushAdminRequest();
        $this->assertSame('error:users', $renderer->renderBody($widget));
        $this->assertTrue($renderer->hasFailed($widget));

        $fail = false;
        $this->pushAdminRequest();
        $this->assertSame('users:total=5;', $renderer->renderBody($widget));
        $this->assertFalse($renderer->hasFailed($widget));
    }

    public function testConfiguratorsPrepareTheWidgetBeforeRenderingIt(): void
    {
        $first = new CallbackWidgetConfigurator(static function (WidgetDto $widget, WidgetContext $context): void {
            $widget->setData('total', 10);
            $widget->setData('range', $context->getAttribute('range'));
            $widget->setData('same', $context->getWidget() === $widget ? 'yes' : 'no');
        });
        $second = new CallbackWidgetConfigurator(static fn (WidgetDto $widget) => $widget->setData('total', 20));
        $unsupported = new CallbackWidgetConfigurator(static fn (WidgetDto $widget) => $widget->setData('total', 30), 'other');
        $renderer = $this->createRenderer([new ArrayWidgetContextProvider(['range' => '30d'])], configurators: [$first, $second, $unsupported]);
        $this->pushAdminRequest();

        $this->assertSame('users:total=20;range=30d;same=yes;', $renderer->renderBody(DummyWidget::new()->setName('users')->getAsDto()));
        $this->assertSame([1, 1, 0], [$first->calls, $second->calls, $unsupported->calls]);
    }

    public function testConfiguratorsCanChangeTheTemplate(): void
    {
        $configurator = new CallbackWidgetConfigurator(static function (WidgetDto $widget): void {
            $widget->setTemplatePath('custom_stat.html.twig');
            $widget->setData('value', 5);
        });
        $renderer = $this->createRenderer(configurators: [$configurator]);
        $this->pushAdminRequest();

        $this->assertSame('custom stat:5', $renderer->renderBody(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testConfiguratorsChangeACopyOfTheWidget(): void
    {
        $configurator = new CallbackWidgetConfigurator(static function (WidgetDto $widget): void {
            $widget->setLabel('Changed');
            $widget->setCustomOption('option', 'changed');
            $widget->setData('total', 10);
        });
        $renderer = $this->createRenderer(configurators: [$configurator]);
        $this->pushAdminRequest();
        $widget = DummyWidget::new('Users')->setName('users')->setCustomOption('option', 'original')->getAsDto();

        $this->assertSame('users:total=10;', $renderer->renderBody($widget));
        $this->assertSame('Users', $widget->getLabel());
        $this->assertSame('original', $widget->getCustomOption('option'));
        $this->assertSame([], $widget->getData());
    }

    public function testConfiguratorsDontRunOnCacheHits(): void
    {
        $configurator = new CallbackWidgetConfigurator(static fn (WidgetDto $widget) => $widget->setData('total', 10));
        $renderer = $this->createRenderer(configurators: [$configurator]);
        $widget = DummyWidget::new()->setName('users')->setCache(60)->getAsDto();

        $this->pushAdminRequest();
        $this->assertSame('users:total=10;', $renderer->renderBody($widget));
        $this->pushAdminRequest();
        $this->assertSame('users:total=10;', $renderer->renderBody($widget));

        $this->assertSame(1, $configurator->calls);
    }

    public function testFailingConfiguratorIsHandledLikeAFailingWidget(): void
    {
        $configurator = new CallbackWidgetConfigurator(static fn () => throw new \RuntimeException('The configurator failed.'));
        $renderer = $this->createRenderer(configurators: [$configurator]);
        $this->pushAdminRequest();
        $widget = DummyWidget::new()->setName('users')->setCache(60)->getAsDto();

        $this->assertSame('error:users', $renderer->renderBody($widget));
        $this->assertTrue($renderer->hasFailed($widget));
        $this->assertSame('error:users', $renderer->renderBody($widget), 'Failures are not cached.');
        $this->assertSame(2, $configurator->calls);
    }

    public function testFailingConfiguratorThrowsInDebugMode(): void
    {
        $configurator = new CallbackWidgetConfigurator(static fn () => throw new \RuntimeException('The configurator failed.'));
        $renderer = $this->createRenderer(debug: true, configurators: [$configurator]);
        $this->pushAdminRequest();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The configurator failed.');

        $renderer->renderBody(DummyWidget::new()->setName('users')->getAsDto());
    }

    public function testRenderError(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest();

        $this->assertSame('error:users', $renderer->renderError(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testNonSerializableContextAttributesThrowAClearError(): void
    {
        $renderer = $this->createRenderer([new ArrayWidgetContextProvider(['callback' => static fn (): int => 1])], debug: true);
        $this->pushAdminRequest();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The attributes returned by the widget context providers are part of the cache key of widgets, so they must be serializable');

        $renderer->renderBody(DummyWidget::new()->setName('users')->setCache(60)->getAsDto());
    }

    public function testFragmentUrl(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest();

        $this->assertSame('/admin/_widget/users', $renderer->getFragmentUrl(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testFragmentUrlKeepsTheQueryString(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest(query: ['range' => '30d', 'filters' => ['status' => 'paid']]);

        $this->assertSame('/admin/_widget/users?range=30d&filters%5Bstatus%5D=paid', $renderer->getFragmentUrl(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testFragmentUrlDropsTheParametersThatChangeTheController(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest(query: [EA::ROUTE_NAME => 'admin_user_index', EA::ROUTE_PARAMS => ['id' => 1], 'range' => '30d']);

        $this->assertSame('/admin/_widget/users?range=30d', $renderer->getFragmentUrl(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testFragmentUrlNameCannotBeOverriddenByTheQueryString(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest(query: ['widgetName' => 'revenue', 'name' => 'Name 1']);

        // a 'name' parameter of the page is kept: it's not the name of the widget
        $this->assertSame('/admin/_widget/users?name=Name%201', $renderer->getFragmentUrl(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testFragmentUrlUsesTheRouteOfTheCurrentDashboard(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest(dashboardRouteName: 'second_admin');

        $this->assertSame('/second-admin/_widget/users', $renderer->getFragmentUrl(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testFragmentUrlIsNullWhenTheRouteDoesNotExist(): void
    {
        $renderer = $this->createRenderer();
        $this->pushAdminRequest(dashboardRouteName: 'legacy_admin');

        $this->assertNull($renderer->getFragmentUrl(DummyWidget::new()->setName('users')->getAsDto()));
    }

    public function testScriptIsIncludedOncePerRequest(): void
    {
        $renderer = $this->createRenderer();

        $this->pushAdminRequest();
        $this->assertTrue($renderer->shouldIncludeScript());
        $this->assertFalse($renderer->shouldIncludeScript());
        $this->assertFalse($renderer->shouldIncludeScript());

        $this->pushAdminRequest();
        $this->assertTrue($renderer->shouldIncludeScript());
        $this->assertFalse($renderer->shouldIncludeScript());
    }

    /**
     * @param iterable<WidgetContextProviderInterface> $contextProviders
     */
    private function createRenderer(iterable $contextProviders = [], bool $debug = false, iterable $configurators = []): WidgetRenderer
    {
        $routes = new RouteCollection();
        $routes->add('admin_widget', new Route('/admin/_widget/{widgetName}', [], ['widgetName' => '[a-zA-Z0-9_-]+']));
        $routes->add('second_admin_widget', new Route('/second-admin/_widget/{widgetName}', [], ['widgetName' => '[a-zA-Z0-9_-]+']));

        return new WidgetRenderer(
            new Environment(new ArrayLoader(self::TEMPLATES), ['strict_variables' => true]),
            new AdminContextProvider($this->requestStack),
            $contextProviders,
            $this->cache,
            new UrlGenerator($routes, new RoutingRequestContext()),
            $this->logger,
            $debug,
            $configurators,
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    private function pushAdminRequest(
        ?UserInterface $user = null,
        string $dashboardControllerFqcn = WidgetsDashboardController::class,
        array $query = [],
        string $locale = 'en',
        ?TemplateRegistry $templateRegistry = null,
        string $dashboardRouteName = 'admin',
    ): Request {
        $request = new Request($query);
        $request->setLocale($locale);

        $dashboardDto = Dashboard::new()->getAsDto();
        $dashboardDto->setRouteName($dashboardRouteName);

        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, AdminContext::forTesting(
            RequestContext::forTesting($request, $user),
            dashboardContext: DashboardContext::forTesting($dashboardDto, $dashboardControllerFqcn),
            i18nContext: I18nContext::forTesting($locale, templateRegistry: $templateRegistry),
        ));

        $this->requestStack->push($request);

        return $request;
    }

    private function createCountingWidget(int &$calls): DummyWidget
    {
        return DummyWidget::new()->setName('users')->setValue('value', static function () use (&$calls): int {
            return ++$calls;
        });
    }

    private function createUserWidget(): DummyWidget
    {
        return DummyWidget::new()->setName('users')->setValue('user', static fn (WidgetContext $context): string => $context->getUser()?->getUserIdentifier() ?? 'anonymous');
    }
}
