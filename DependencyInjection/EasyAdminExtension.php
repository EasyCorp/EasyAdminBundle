<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Resolves all the backend configuration values and most of the entities
 * configuration. The information that must resolved during runtime is handled
 * by the Configurator class.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // process bundle's configuration parameters
        $backendConfiguration = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('easyadmin.config', $backendConfiguration);

        // load bundle's services
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('form.xml');

        // Don't register our exception listener if debug is enabled
        if ($container->getParameter('kernel.debug')) {
            $container->removeDefinition('easyadmin.listener.exception');
        }

        $this->ensureBackwardCompatibility($container);
    }

    /**
     * Makes some tweaks in order to ensure backward compatibilities
     * with supported versions of Symfony components.
     *
     * @param ContainerBuilder $container
     */
    private function ensureBackwardCompatibility(ContainerBuilder $container)
    {
        // BC for Symfony 2.3 and Request Stack
        $isRequestStackAvailable = class_exists('Symfony\\Component\\HttpFoundation\\RequestStack');
        if (!$isRequestStackAvailable) {
            $needsSetRequestMethodCall = array('easyadmin.listener.request_post_initialize', 'easyadmin.form.type.extension');
            foreach ($needsSetRequestMethodCall as $serviceId) {
                $container
                    ->getDefinition($serviceId)
                    ->addMethodCall('setRequest', array(
                        new Reference('request', ContainerInterface::NULL_ON_INVALID_REFERENCE, false),
                    ))
                ;
            }
        }

        // BC for legacy form component
        $useLegacyFormComponent = false === class_exists('Symfony\\Component\\Form\\Util\\StringUtil');
        if (!$useLegacyFormComponent) {
            $container
                ->getDefinition('easyadmin.form.type')
                ->clearTag('form.type')
                ->addTag('form.type')
            ;
        }
    }
}
