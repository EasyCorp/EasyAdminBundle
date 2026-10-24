<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit;

use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\UiKitController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class EasyAdminBundleTest extends TestCase
{
    #[DataProvider('provideUiKitParameterValues')]
    public function testUiKitIsEnabledAccordingToTheParameter(bool $debug, mixed $parameterValue, bool $isEnabled): void
    {
        $container = $this->createContainer($debug);
        if (null !== $parameterValue) {
            $container->setParameter('ui_kit_switch', true);
            $container->setParameter(EasyAdminBundle::PARAMETER_UI_KIT_ENABLED, $parameterValue);
        }

        $containerConfigurator = $this->createContainerConfigurator();

        (new EasyAdminBundle())->loadExtension([], $containerConfigurator, $container);

        $this->assertSame($isEnabled, $container->hasDefinition(UiKitController::class));
    }

    public static function provideUiKitParameterValues(): iterable
    {
        yield 'debug mode without parameter' => [true, null, true];
        yield 'no debug mode without parameter' => [false, null, false];
        yield 'parameter disables it in debug mode' => [true, false, false];
        yield 'parameter enables it without debug mode' => [false, true, true];
        yield 'parameter referencing another parameter' => [false, '%ui_kit_switch%', true];
    }

    public function testUiKitParameterRejectsEnvironmentVariables(): void
    {
        $container = $this->createContainer(false);
        $container->setParameter(EasyAdminBundle::PARAMETER_UI_KIT_ENABLED, '%env(bool:UI_KIT_ENABLED)%');
        $containerConfigurator = $this->createContainerConfigurator();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "easyadmin.ui_kit.enabled" parameter can\'t use environment variables');

        (new EasyAdminBundle())->loadExtension([], $containerConfigurator, $container);
    }

    private function createContainer(bool $debug): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', $debug);

        return $container;
    }

    private function createContainerConfigurator(): ContainerConfigurator
    {
        $container = $this->createStub(ContainerBuilder::class);
        $phpLoader = $this->createStub(PhpFileLoader::class);
        $instanceof = [];

        return new ContainerConfigurator($container, $phpLoader, $instanceof, __DIR__, __FILE__);
    }
}
