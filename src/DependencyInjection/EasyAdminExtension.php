<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminExtension extends Extension
{
    public const TAG_CRUD_CONTROLLER = 'ea.crud_controller';
    public const TAG_DASHBOARD_CONTROLLER = 'ea.dashboard_controller';
    public const TAG_FIELD_CONFIGURATOR = 'ea.field_configurator';
    public const TAG_FILTER_CONFIGURATOR = 'ea.filter_configurator';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(DashboardControllerInterface::class)
            ->addTag(self::TAG_DASHBOARD_CONTROLLER);

        $container->registerForAutoconfiguration(CrudControllerInterface::class)
            ->addTag(self::TAG_CRUD_CONTROLLER);

        $container->registerForAutoconfiguration(FieldConfiguratorInterface::class)
            ->addTag(self::TAG_FIELD_CONFIGURATOR);

        $container->registerForAutoconfiguration(FilterConfiguratorInterface::class)
            ->addTag(self::TAG_FILTER_CONFIGURATOR);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }
}
