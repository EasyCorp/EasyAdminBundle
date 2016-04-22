<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ...
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Processor
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function processConfig()
    {
        $backendConfig = $this->container->getParameter('easyadmin.config');

        $configPasses = array(
            new NormalizerConfigPass(),
            new DesignConfigPass($this->container->get('twig'), $this->container->getParameter('kernel.debug')),
            new MenuConfigPass(),
            new ActionConfigPass(),
            new MetadataConfigPass($this->container->get('doctrine')),
            new PropertyConfigPass(),
            new ViewConfigPass(),
            new TemplateConfigPass($this->container->getParameter('kernel.root_dir').'/Resources/views'),
            new DefaultConfigPass(),
        );

        foreach ($configPasses as $configPass) {
            $backendConfig = $configPass->process($backendConfig);
        }

        return $backendConfig;
    }
}
