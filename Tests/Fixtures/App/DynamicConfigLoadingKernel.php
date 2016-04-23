<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The kernel used in the unit tests related to the configuration processing.
 */
class DynamicConfigLoadingKernel extends AppKernel
{
    private $backendConfig;

    public function __construct($backendConfig)
    {
        parent::__construct('test', true);
        $this->backendConfig = $backendConfig;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);

        $backendConfig = $this->backendConfig; // needed for PHP 5.3
        $loader->load(function (ContainerBuilder $container) use ($backendConfig) {
//var_dump("LOADING THIS CONFIG -->", md5(serialize($backendConfig)), $backendConfig);
            $container->loadFromExtension('easy_admin', $backendConfig);
        });
    }
}
