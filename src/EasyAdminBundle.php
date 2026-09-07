<?php

namespace EasyCorp\Bundle\EasyAdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Action\ActionsExtensionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends AbstractBundle
{
    public const VERSION = '5.5.2-DEV';
    public const TAG_CRUD_CONTROLLER = 'ea.crud_controller';
    public const TAG_DASHBOARD_CONTROLLER = 'ea.dashboard_controller';
    public const TAG_ADMIN_ROUTE_CONTROLLER = 'ea.admin_route_controller';
    public const TAG_FIELD_CONFIGURATOR = 'ea.field_configurator';
    public const TAG_FILTER_CONFIGURATOR = 'ea.filter_configurator';
    public const TAG_ACTIONS_EXTENSION = 'ea.actions_extension';

    /**
     * @phpstan-ignore missingType.iterableValue
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(AdminRoute::class,
            // @phpstan-ignore-next-line argument.type The reflection subtypes specify where the attribute can be used
            static function (Definition $definition, AdminRoute $attribute, \ReflectionClass|\ReflectionMethod $reflection): void {
                $definition->addTag(self::TAG_ADMIN_ROUTE_CONTROLLER);
            });

        $container->registerForAutoconfiguration(DashboardControllerInterface::class)
            ->addTag(self::TAG_DASHBOARD_CONTROLLER);

        $container->registerForAutoconfiguration(CrudControllerInterface::class)
            ->addTag(self::TAG_CRUD_CONTROLLER);

        $container->registerForAutoconfiguration(FieldConfiguratorInterface::class)
            ->addTag(self::TAG_FIELD_CONFIGURATOR);

        $container->registerForAutoconfiguration(FilterConfiguratorInterface::class)
            ->addTag(self::TAG_FILTER_CONFIGURATOR);

        $container->registerForAutoconfiguration(ActionsExtensionInterface::class)
            ->addTag(self::TAG_ACTIONS_EXTENSION);

        $configurator->import('../config/services.php');
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('twig_component', [
            'defaults' => [
                'EasyCorp\\Bundle\\EasyAdminBundle\\Twig\\Component\\' => [
                    'template_directory' => '@EasyAdmin/components/',
                    'name_prefix' => 'ea',
                ],
            ],
        ]);

        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');

        $bundleTemplatesOverrideDir = $projectDir.'/templates/bundles/EasyAdminBundle/';
        $container->prependExtensionConfig('twig', [
            'paths' => is_dir($bundleTemplatesOverrideDir)
                ? [
                    'templates/bundles/EasyAdminBundle/' => 'ea',
                    \dirname(__DIR__).'/templates/' => 'ea',
                ]
                : [
                    \dirname(__DIR__).'/templates/' => 'ea',
                ],
        ]);
    }
}
