<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\EventListener\ExceptionListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(CrudControllerInterface::class)
            ->addTag('ea.crud_controller')
        ;

        // this parameter is created for BC reasons but it can be deleted in future releases
        $container->setParameter('easyadmin.config', []);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        // use EasyAdmin exception listener only in 'dev'
        if ($container->getParameter('kernel.debug')) {
            $container->removeDefinition(ExceptionListener::class);
        }
    }
}
