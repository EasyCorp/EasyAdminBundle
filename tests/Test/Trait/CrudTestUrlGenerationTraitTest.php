<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Test\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\SecureDashboardController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CrudTestUrlGenerationTraitTest extends KernelTestCase
{
    public const TEST_CONTROLLER = CategoryCrudController::class;
    public const TEST_DASHBOARD = DashboardController::class;

    private AdminUrlGeneratorInterface $adminUrlGenerator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
    }

    public function testGenericUrlIndexGeneration()
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(): string
            {
                return $this->generateIndexUrl();
            }
        };

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(self::TEST_DASHBOARD)
            ->setController(self::TEST_CONTROLLER)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test());
    }

    /**
     * @dataProvider specificDashboardAndControllerFqcn
     */
    public function testGenericUrlIndexGenerationWithSpecificDashboardAndController(
        ?string $dashboardFqcn,
        ?string $controllerFqcn,
    ) {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(?string $dashboardFqcn, ?string $controllerFqcn): string
            {
                return $this->generateIndexUrl(dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
            }
        };

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard($dashboardFqcn ?? self::TEST_DASHBOARD)
            ->setController($controllerFqcn ?? self::TEST_CONTROLLER)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test($dashboardFqcn, $controllerFqcn));
    }

    public function testUrlDetailGeneration()
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(string|int $id, ?string $dashboardFqcn = null, ?string $controllerFqcn = null): string
            {
                return $this->generateDetailUrl($id, dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
            }
        };

        $entityId = 'Test_id';
        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(self::TEST_DASHBOARD)
            ->setController(self::TEST_CONTROLLER)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test($entityId));
    }

    /**
     * @dataProvider specificDashboardAndControllerFqcn
     */
    public function testUrlDetailGenerationWithSpecificDashboardAndControllerFqcn(
        ?string $dashboardFqcn,
        ?string $controllerFqcn,
    ) {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(string|int $id, ?string $dashboardFqcn, ?string $controllerFqcn): string
            {
                return $this->generateDetailUrl($id, dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
            }
        };

        $entityId = 'Test_id';
        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard($dashboardFqcn ?? self::TEST_DASHBOARD)
            ->setController($controllerFqcn ?? self::TEST_CONTROLLER)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test($entityId, $dashboardFqcn, $controllerFqcn));
    }

    public function testUrlNewFormGeneration()
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(): string
            {
                return $this->generateNewFormUrl();
            }
        };

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(self::TEST_DASHBOARD)
            ->setController(self::TEST_CONTROLLER)
            ->setAction(Action::NEW)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test());
    }

    /**
     * @dataProvider specificDashboardAndControllerFqcn
     */
    public function testUrlNewFormGenerationWithSpecificDashboardAndControllerFqcn(
        ?string $dashboardFqcn,
        ?string $controllerFqcn,
    ) {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(?string $dashboardFqcn, ?string $controllerFqcn): string
            {
                return $this->generateNewFormUrl(dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
            }
        };

        $entityId = 'Test_id';
        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard($dashboardFqcn ?? self::TEST_DASHBOARD)
            ->setController($controllerFqcn ?? self::TEST_CONTROLLER)
            ->setAction(Action::NEW)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test($dashboardFqcn, $controllerFqcn));
    }

    public function testUrlEditFormGeneration()
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(string|int $id): string
            {
                return $this->generateEditFormUrl($id);
            }
        };

        $entityId = 'Test_id';
        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(self::TEST_DASHBOARD)
            ->setController(self::TEST_CONTROLLER)
            ->setAction(Action::EDIT)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test($entityId));
    }

    /**
     * @dataProvider specificDashboardAndControllerFqcn
     */
    public function testUrlEditGenerationWithSpecificDashboardAndControllerFqcn(
        ?string $dashboardFqcn,
        ?string $controllerFqcn,
    ) {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(string|int $id, ?string $dashboardFqcn, ?string $controllerFqcn): string
            {
                return $this->generateEditFormUrl($id, dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
            }
        };

        $entityId = 'Test_id';
        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard($dashboardFqcn ?? self::TEST_DASHBOARD)
            ->setController($controllerFqcn ?? self::TEST_CONTROLLER)
            ->setAction(Action::EDIT)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test($entityId, $dashboardFqcn, $controllerFqcn));
    }

    public function testUrlRenderFiltersGeneration()
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(): string
            {
                return $this->generateFilterRenderUrl();
            }
        };

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(self::TEST_DASHBOARD)
            ->setController(self::TEST_CONTROLLER)
            // No defined const in EasyCorp\Bundle\EasyAdminBundle\Config\Action so need to write it by hand
            ->setAction('renderFilters')
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test());
    }

    /**
     * @dataProvider specificDashboardAndControllerFqcn
     */
    public function testUrlRenderFiltersGenerationWithSpecificDashboardAndControllerFqcn(
        ?string $dashboardFqcn,
        ?string $controllerFqcn,
    ) {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(?string $dashboardFqcn, ?string $controllerFqcn): string
            {
                return $this->generateFilterRenderUrl(dashboardFqcn: $dashboardFqcn, controllerFqcn: $controllerFqcn);
            }
        };

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard($dashboardFqcn ?? self::TEST_DASHBOARD)
            ->setController($controllerFqcn ?? self::TEST_CONTROLLER)
            // No defined const in EasyCorp\Bundle\EasyAdminBundle\Config\Action so need to write it by hand
            ->setAction('renderFilters')
            ->generateUrl()
        ;

        self::assertEquals($expectedUrl, $testClass->test($dashboardFqcn, $controllerFqcn));
    }

    /**
     * @param array<string, string> $options
     *
     * @dataProvider genericDataProvider
     */
    public function testGenericUrlGeneration(string $action, string|int|null $entityId = null, array $options = [])
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(string $action, string|int|null $entityId = null, array $options = []): string
            {
                return $this->getCrudUrl($action, $entityId, $options);
            }
        };

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(self::TEST_DASHBOARD)
            ->setController(self::TEST_CONTROLLER)
            ->setAction($action)
        ;

        if (null !== $entityId) {
            $expectedUrl->setEntityId($entityId);
        }

        foreach ($options as $key => $value) {
            $expectedUrl->set($key, $value);
        }

        self::assertEquals($expectedUrl->generateUrl(), $testClass->test($action, $entityId, $options));
    }

    public function genericDataProvider(): \Generator
    {
        yield 'only Action' => [
            'customAction',
        ];

        yield 'action with string id' => [
            'action_with_id', 'entityId',
        ];

        yield 'action with int id' => [
            'action_with_id', 159,
        ];

        yield 'Action with specific options' => [
            'action_with_option', null, ['option_1' => 'fantastic_value'],
        ];
    }

    public function specificDashboardAndControllerFqcn(): \Generator
    {
        yield 'only controller' => [
            null,
            BlogPostCrudController::class,
        ];

        yield 'same Dashboard different controller' => [
            self::TEST_DASHBOARD,
            BlogPostCrudController::class,
        ];

        yield 'only Dashboard' => [
            SecureDashboardController::class,
            null,
        ];

        yield 'same Controller different Dashboard' => [
            SecureDashboardController::class,
            self::TEST_CONTROLLER,
        ];

        yield 'both different' => [
            SecureDashboardController::class,
            BlogPostCrudController::class,
        ];

        yield 'both null for both same' => [
            null,
            null,
        ];
    }
}

class CrudTestUrlGenerationTraitTestClass
{
    use CrudTestUrlGeneration;

    protected AdminUrlGenerator $adminUrlGenerator;

    public function __construct(AdminUrlGeneratorInterface $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    protected function getControllerFqcn(): string
    {
        return CrudTestUrlGenerationTraitTest::TEST_CONTROLLER;
    }

    protected function getDashboardFqcn(): string
    {
        return CrudTestUrlGenerationTraitTest::TEST_DASHBOARD;
    }
}
