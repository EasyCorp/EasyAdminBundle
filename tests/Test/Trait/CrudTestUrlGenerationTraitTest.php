<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Test\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\Exception\InvalidClassPropertyTypeException;
use EasyCorp\Bundle\EasyAdminBundle\Test\Exception\MissingClassMethodException;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CrudTestUrlGenerationTraitTest extends KernelTestCase
{
    public const TEST_CONTROLLER = CategoryCrudController::class;
    public const TEST_DASHBOARD = DashboardController::class;

    private AdminUrlGenerator $adminUrlGenerator;

    protected function setUp(): void
    {
        static::bootKernel();
        $this->adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
    }

    public function testMissingPropertyOnClassUsingTrait(): void
    {
        $testClass = new class() extends CrudTestUrlGenerationTraitTestClassMissingProperty {
            public function test(): string
            {
                return $this->generateIndexUrl();
            }
        };

        static::expectException(InvalidClassPropertyTypeException::class);
        $testClass->test();
    }

    public function testMissingMethodOnClassUsingTrait(): void
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClassMissingMethod {
            public function test(): string
            {
                return $this->generateIndexUrl();
            }
        };

        static::expectException(MissingClassMethodException::class);
        $testClass->test();
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
            ->setDashboard(static::TEST_DASHBOARD)
            ->setController(static::TEST_CONTROLLER)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;

        static::assertEquals($expectedUrl, $testClass->test());
    }

    public function testUrlDetailGeneration()
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(string|int $id): string
            {
                return $this->generateDetailUrl($id);
            }
        };

        $entityId = 'Test_id';
        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(static::TEST_DASHBOARD)
            ->setController(static::TEST_CONTROLLER)
            ->setAction(Action::DETAIL)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        static::assertEquals($expectedUrl, $testClass->test($entityId));
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
            ->setDashboard(static::TEST_DASHBOARD)
            ->setController(static::TEST_CONTROLLER)
            ->setAction(Action::NEW)
            ->generateUrl()
        ;

        static::assertEquals($expectedUrl, $testClass->test());
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
            ->setDashboard(static::TEST_DASHBOARD)
            ->setController(static::TEST_CONTROLLER)
            ->setAction(Action::EDIT)
            ->setEntityId($entityId)
            ->generateUrl()
        ;

        static::assertEquals($expectedUrl, $testClass->test($entityId));
    }

    public function testUrlRenderFiltersGeneration()
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(): string
            {
                return $this->generateFilterRenderUrl();
            }
        };

        $indexUrl = $this->adminUrlGenerator
            ->setDashboard(static::TEST_DASHBOARD)
            ->setController(static::TEST_CONTROLLER)
            ->setAction(Action::INDEX)
            ->generateUrl()
        ;
        $referrer = preg_replace('/^.*(\/.*)$/', '$1', $indexUrl);

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(static::TEST_DASHBOARD)
            ->setController(static::TEST_CONTROLLER)
            // No defined const in EasyCorp\Bundle\EasyAdminBundle\Config\Action so need to write it by hand
            ->setAction('renderFilters')
            ->setReferrer($referrer)
            ->generateUrl()
        ;

        static::assertEquals($expectedUrl, $testClass->test());
    }

    /**
     * @param array<string, string> $options
     *
     * @dataProvider genericDataProvider
     */
    public function testGenericUrlGeneration(string $action, string|int $entityId = null, array $options = [])
    {
        $testClass = new class($this->adminUrlGenerator) extends CrudTestUrlGenerationTraitTestClass {
            public function test(string $action, string|int $entityId = null, array $options = []): string
            {
                return $this->getCrudUrl($action, $entityId, $options);
            }
        };

        $expectedUrl = $this->adminUrlGenerator
            ->setDashboard(static::TEST_DASHBOARD)
            ->setController(static::TEST_CONTROLLER)
            ->setAction($action)
        ;

        if (null !== $entityId) {
            $expectedUrl->setEntityId($entityId);
        }

        foreach ($options as $key => $value) {
            $expectedUrl->set($key, $value);
        }

        static::assertEquals($expectedUrl->generateUrl(), $testClass->test($action, $entityId, $options));
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
}

class CrudTestUrlGenerationTraitTestClass
{
    use CrudTestUrlGeneration;

    protected AdminUrlGenerator $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
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

class CrudTestUrlGenerationTraitTestClassMissingProperty
{
    use CrudTestUrlGeneration;

    protected function getControllerFqcn(): string
    {
        return CrudTestUrlGenerationTraitTest::TEST_CONTROLLER;
    }

    protected function getDashboardFqcn(): string
    {
        return CrudTestUrlGenerationTraitTest::TEST_DASHBOARD;
    }
}

class CrudTestUrlGenerationTraitTestClassMissingMethod
{
    use CrudTestUrlGeneration;

    protected AdminUrlGenerator $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }
}
