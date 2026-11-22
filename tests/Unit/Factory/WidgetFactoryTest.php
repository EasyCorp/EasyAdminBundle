<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetSectionDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\WidgetFactory;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\DummyWidget;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\InterfaceOnlyDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\RecordingAuthorizationChecker;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\WidgetsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\WidgetsWithoutRenderDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetSection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Translation\TranslatableMessage;

class WidgetFactoryTest extends TestCase
{
    private RequestStack $requestStack;
    private RecordingAuthorizationChecker $authorizationChecker;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->authorizationChecker = new RecordingAuthorizationChecker(['ROLE_ADMIN']);
        WidgetsDashboardController::$configureWidgetsCalls = 0;
    }

    public function testDashboardWithoutConfigureWidgetsMethod(): void
    {
        $widgets = $this->createFactory()->create(new InterfaceOnlyDashboardController());

        $this->assertTrue($widgets->isEmpty());
        $this->assertSame([], $widgets->getSections());
    }

    public function testDashboardWithoutWidgets(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController());

        $this->assertTrue($widgets->isEmpty());
    }

    public function testNames(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new('Latest orders'),
            DummyWidget::new('Total users')->setName('users'),
            DummyWidget::new(),
            DummyWidget::new(new TranslatableMessage('widget.revenue')),
            DummyWidget::new('!!!'),
            DummyWidget::new('Ventas del año'),
        ]));

        $this->assertSame(['latest-orders', 'users', 'widget-3', 'widget-4', 'widget-5', 'ventas-del-ano'], array_keys($widgets->all()));
    }

    public function testPositionalNamesCountTheDeniedWidgets(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new()->setPermission('ROLE_SUPER_ADMIN'),
            DummyWidget::new(),
        ]));

        $this->assertSame(['widget-2'], array_keys($widgets->all()));
        $this->assertTrue($widgets->isDenied('widget-1'));
    }

    #[DataProvider('provideDuplicatedWidgets')]
    public function testDuplicatedWidgetNames(array $widgets): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s::configureWidgets()" method defines two widgets with the same name ("users").', WidgetsDashboardController::class));

        $this->createFactory()->create(new WidgetsDashboardController($widgets));
    }

    public static function provideDuplicatedWidgets(): iterable
    {
        yield 'same label' => [[DummyWidget::new('Users'), DummyWidget::new('Users')]];
        yield 'explicit name and label' => [[DummyWidget::new('Total')->setName('users'), DummyWidget::new('Users')]];
        yield 'second widget is denied' => [[DummyWidget::new('Users'), DummyWidget::new('Users')->setPermission('ROLE_SUPER_ADMIN')]];
        yield 'first widget is denied' => [[DummyWidget::new('Users')->setPermission('ROLE_SUPER_ADMIN'), DummyWidget::new('Users')]];
        yield 'in different sections' => [[WidgetSection::new('Sales'), DummyWidget::new('Users'), WidgetSection::new('Marketing'), DummyWidget::new('Users')]];
        yield 'in a denied section' => [[DummyWidget::new('Users'), WidgetSection::new('Sales')->setPermission('ROLE_SUPER_ADMIN'), DummyWidget::new('Users')]];
        yield 'toolbar widget' => [[DummyWidget::new('Users')->setPlacement(WidgetPlacement::Toolbar), DummyWidget::new('Users')]];
    }

    public function testExplicitNameThatLooksLikeAPositionalName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('two widgets with the same name ("widget-2")');

        $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new()->setName('widget-2'),
            DummyWidget::new(),
        ]));
    }

    public function testDuplicatedSectionNames(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The "%s::configureWidgets()" method defines two widget sections with the same name ("sales").', WidgetsDashboardController::class));

        $this->createFactory()->create(new WidgetsDashboardController([
            WidgetSection::new('Sales'),
            DummyWidget::new('Orders'),
            WidgetSection::new('Marketing')->setName('sales'),
            DummyWidget::new('Visits'),
        ]));
    }

    public function testDuplicatedSectionNamesAreDetectedEvenIfTheSectionsAreEmptyOrDenied(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('two widget sections with the same name ("sales")');

        $this->createFactory()->create(new WidgetsDashboardController([
            WidgetSection::new('Sales')->setPermission('ROLE_SUPER_ADMIN'),
            WidgetSection::new('Sales'),
        ]));
    }

    public function testSectionsWithoutNameCanBeRepeated(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            WidgetSection::new(),
            DummyWidget::new('Orders'),
            WidgetSection::new(new TranslatableMessage('section.sales')),
            DummyWidget::new('Visits'),
            WidgetSection::new('!!!'),
            DummyWidget::new('Users'),
        ]));

        $this->assertCount(3, $widgets->getSections());
        foreach ($widgets->getSections() as $section) {
            $this->assertNull($section->getName());
        }
    }

    #[DataProvider('provideWidgetsThatNeedAStableName')]
    public function testWidgetsThatNeedAStableName(DummyWidget $widget): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The widget "%s" (in position 2 of "%s::configureWidgets()") uses "setLazy()", "setCache()" or "setRefreshInterval()", so it needs a stable name', DummyWidget::class, WidgetsDashboardController::class));

        $this->createFactory()->create(new WidgetsDashboardController([DummyWidget::new('Users'), $widget]));
    }

    public static function provideWidgetsThatNeedAStableName(): iterable
    {
        yield 'lazy without label' => [DummyWidget::new()->setLazy()];
        yield 'cached with translatable label' => [DummyWidget::new(new TranslatableMessage('widget.users'))->setCache(60)];
        yield 'refreshing with an empty slug' => [DummyWidget::new('!!!')->setRefreshInterval(30)];
    }

    public function testWidgetsWithStableNames(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new()->setName('users')->setLazy(),
            DummyWidget::new(new TranslatableMessage('widget.orders'))->setName('orders')->setCache(60),
            DummyWidget::new('Revenue')->setRefreshInterval(30),
        ]));

        $this->assertSame(['users', 'orders', 'revenue'], array_keys($widgets->all()));
    }

    #[DataProvider('provideWidgetsLoadedSeparately')]
    public function testWidgetsLoadedSeparatelyNeedTheRenderWidgetMethod(DummyWidget $widget): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The widget "users" uses "setLazy()" or "setRefreshInterval()", which load the widget contents in a separate request served by the "renderWidget()" method of the dashboard controller. The "%s" class doesn\'t have that method', WidgetsWithoutRenderDashboardController::class));

        $this->createFactory()->create(new WidgetsWithoutRenderDashboardController([$widget]));
    }

    public static function provideWidgetsLoadedSeparately(): iterable
    {
        yield 'lazy' => [DummyWidget::new('Users')->setLazy()];
        yield 'refreshing' => [DummyWidget::new('Users')->setRefreshInterval(60)];
    }

    public function testDashboardWithoutRenderWidgetMethodCanDisplayOtherWidgets(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsWithoutRenderDashboardController([
            DummyWidget::new('Users')->setCache(60),
            DummyWidget::new('Orders'),
        ]));

        $this->assertSame(['users', 'orders'], array_keys($widgets->all()));
    }

    #[DataProvider('provideInvalidItems')]
    public function testItemsThatAreNotWidgets(mixed $item, string $expectedType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The "%s::configureWidgets()" method must only return objects that implement "EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface" or instances of "%s" ("%s" given).', WidgetsDashboardController::class, WidgetSection::class, $expectedType));

        $this->createFactory()->create(new WidgetsDashboardController([DummyWidget::new('Users'), $item]));
    }

    public static function provideInvalidItems(): iterable
    {
        yield 'string' => ['users', 'string'];
        yield 'null' => [null, 'null'];
        yield 'DTO instead of the widget' => [DummyWidget::new('Orders')->getAsDto(), 'EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto'];
    }

    public function testDeniedWidgetsAreDroppedAndReported(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new('Users'),
            DummyWidget::new('Revenue')->setPermission('ROLE_SUPER_ADMIN'),
            DummyWidget::new('Orders')->setPermission('ROLE_ADMIN'),
            DummyWidget::new('Range')->setPermission('ROLE_SUPER_ADMIN')->setPlacement(WidgetPlacement::Toolbar),
        ]));

        $this->assertSame(['users', 'orders'], array_keys($widgets->all()));
        $this->assertTrue($widgets->isDenied('revenue'));
        $this->assertTrue($widgets->isDenied('range'));
        $this->assertFalse($widgets->isDenied('users'));
        $this->assertSame([], $widgets->getToolbarWidgets());
    }

    public function testPermissionSubjectIsTheDto(): void
    {
        $widget = DummyWidget::new('Revenue')->setPermission('ROLE_SUPER_ADMIN');
        $section = WidgetSection::new('Sales')->setPermission('ROLE_ADMIN');

        $this->createFactory()->create(new WidgetsDashboardController([$section, $widget]));

        $this->assertCount(2, $this->authorizationChecker->calls);
        $this->assertSame('ROLE_ADMIN', $this->authorizationChecker->calls[0]['attribute']);
        $this->assertInstanceOf(WidgetSectionDto::class, $this->authorizationChecker->calls[0]['subject']);
        $this->assertSame('sales', $this->authorizationChecker->calls[0]['subject']->getName());
        $this->assertSame('ROLE_SUPER_ADMIN', $this->authorizationChecker->calls[1]['attribute']);
        $this->assertInstanceOf(WidgetDto::class, $this->authorizationChecker->calls[1]['subject']);
        $this->assertSame('revenue', $this->authorizationChecker->calls[1]['subject']->getName());
    }

    public function testDeniedSectionHidesAllItsWidgets(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            WidgetSection::new('Sales')->setPermission('ROLE_SUPER_ADMIN'),
            DummyWidget::new('Orders'),
            DummyWidget::new('Revenue')->setPermission('ROLE_ADMIN'),
            DummyWidget::new('Range')->setPlacement(WidgetPlacement::Toolbar),
            WidgetSection::new('Marketing'),
            DummyWidget::new('Visits'),
        ]));

        $this->assertSame(['visits'], array_keys($widgets->all()));
        $this->assertNull($widgets->getSection('sales'));
        $this->assertSame(['marketing'], array_map(static fn ($section) => $section->getName(), $widgets->getSections()));
        $this->assertTrue($widgets->isDenied('orders'));
        $this->assertTrue($widgets->isDenied('revenue'));
        $this->assertTrue($widgets->isDenied('range'));
    }

    public function testSections(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new('Users'),
            DummyWidget::new('Orders'),
            WidgetSection::new('Sales', 'fa fa-chart-line'),
            DummyWidget::new('Revenue'),
            WidgetSection::new('Empty'),
            WidgetSection::new('Only denied widgets'),
            DummyWidget::new('Refunds')->setPermission('ROLE_SUPER_ADMIN'),
            WidgetSection::new('Marketing')->setName('mkt'),
            DummyWidget::new('Visits'),
        ]));

        $sections = $widgets->getSections();
        $this->assertCount(3, $sections);

        $this->assertNull($sections[0]->getName());
        $this->assertSame(['users', 'orders'], array_map(static fn ($widget) => $widget->getName(), $sections[0]->getWidgets()));

        $this->assertSame('sales', $sections[1]->getName());
        $this->assertSame('fa fa-chart-line', $sections[1]->getIcon());
        $this->assertSame(['revenue'], array_map(static fn ($widget) => $widget->getName(), $sections[1]->getWidgets()));

        $this->assertSame('mkt', $sections[2]->getName());
        $this->assertSame(['visits'], array_map(static fn ($widget) => $widget->getName(), $sections[2]->getWidgets()));

        $this->assertNull($widgets->getSection('empty'));
    }

    public function testFirstItemIsASection(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            WidgetSection::new('Sales'),
            DummyWidget::new('Revenue'),
        ]));

        $this->assertCount(1, $widgets->getSections());
        $this->assertSame('sales', $widgets->getSections()[0]->getName());
    }

    public function testToolbarWidgetsAreSeparated(): void
    {
        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new('Range')->setPlacement(WidgetPlacement::Toolbar),
            DummyWidget::new('Users'),
            WidgetSection::new('Sales'),
            DummyWidget::new('Currency')->setPlacement(WidgetPlacement::Toolbar),
            WidgetSection::new('Marketing'),
            DummyWidget::new('Tenant')->setPlacement(WidgetPlacement::Toolbar),
        ]));

        $this->assertSame(['range', 'currency', 'tenant'], array_map(static fn ($widget) => $widget->getName(), $widgets->getToolbarWidgets()));
        $this->assertCount(1, $widgets->getSections());
        $this->assertSame(['users'], array_map(static fn ($widget) => $widget->getName(), $widgets->getSections()[0]->getWidgets()));
        $this->assertTrue($widgets->has('currency'));
    }

    public function testWidgetDataIsNotComputed(): void
    {
        $failingClosure = static fn (): never => throw new \RuntimeException('The data of widgets must not be computed when building them.');

        $widgets = $this->createFactory()->create(new WidgetsDashboardController([
            DummyWidget::new('Users')->setValue('total', $failingClosure),
            DummyWidget::new('Revenue')->setValue('total', $failingClosure)->setPermission('ROLE_SUPER_ADMIN'),
            DummyWidget::new('Orders')->setValue('total', $failingClosure)->setLazy(),
        ]));

        $this->assertSame(['users', 'orders'], array_keys($widgets->all()));
        $this->assertSame($failingClosure, $widgets->get('users')?->getData()['total']);
    }

    public function testWidgetsCanBeYieldedByAGenerator(): void
    {
        $generator = (static function () {
            yield DummyWidget::new('Users');
            yield WidgetSection::new('Sales');
            yield DummyWidget::new('Revenue');
        })();

        $widgets = $this->createFactory()->create(new WidgetsDashboardController($generator));

        $this->assertSame(['users', 'revenue'], array_keys($widgets->all()));
    }

    public function testGetWidgetsWithoutAdminContext(): void
    {
        $factory = $this->createFactory();

        $this->assertTrue($factory->getWidgets()->isEmpty());

        $this->requestStack->push(new Request());
        $this->assertTrue($factory->getWidgets()->isEmpty());
        $this->assertSame(0, WidgetsDashboardController::$configureWidgetsCalls);
    }

    public function testGetWidgetsIsMemoizedPerRequest(): void
    {
        $factory = $this->createFactory();
        $this->pushAdminRequest(WidgetsDashboardController::class);

        $widgets = $factory->getWidgets();

        $this->assertSame($widgets, $factory->getWidgets());
        $this->assertSame(1, WidgetsDashboardController::$configureWidgetsCalls);
    }

    public function testGetWidgetsIsBuiltAgainForEachRequest(): void
    {
        $factory = $this->createFactory();

        $this->pushAdminRequest(WidgetsDashboardController::class);
        $firstWidgets = $factory->getWidgets();

        $this->pushAdminRequest(WidgetsDashboardController::class);
        $secondWidgets = $factory->getWidgets();

        $this->assertNotSame($firstWidgets, $secondWidgets);
        $this->assertSame(2, WidgetsDashboardController::$configureWidgetsCalls);

        $this->requestStack->pop();
        $this->assertSame($firstWidgets, $factory->getWidgets());
        $this->assertSame(2, WidgetsDashboardController::$configureWidgetsCalls);
    }

    public function testGetWidgetsOfDashboardWithoutConfigureWidgetsMethod(): void
    {
        $factory = $this->createFactory();
        $this->pushAdminRequest(InterfaceOnlyDashboardController::class);

        $this->assertTrue($factory->getWidgets()->isEmpty());
    }

    private function createFactory(): WidgetFactory
    {
        return new WidgetFactory(
            new AdminContextProvider($this->requestStack),
            new ControllerFactory(new ControllerResolver()),
            new AuthorizationChecker($this->authorizationChecker),
        );
    }

    private function pushAdminRequest(string $dashboardControllerFqcn): void
    {
        $request = new Request();
        $request->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, AdminContext::forTesting(
            RequestContext::forTesting($request),
            dashboardContext: DashboardContext::forTesting(dashboardControllerFqcn: $dashboardControllerFqcn),
        ));

        $this->requestStack->push($request);
    }
}
