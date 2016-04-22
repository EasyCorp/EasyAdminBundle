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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes all the defined "config passes" to transform the original backend
 * configuration into the fully processed configuration used by the application.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Processor
{
    /** @var Registry */
    private $doctrine;
    /** @var \Twig_Environment */
    private $twig;
    private $parameters;

    public function __construct(Registry $doctrine, \Twig_Environment $twig, array $parameters)
    {
        $this->doctrine = $doctrine;
        $this->twig = $twig;
        $this->parameters = $parameters;
    }

    /**
     * Takes the 'easyadmin.config' container parameter and turns it into the
     * fully processed configuration by applying the different "config passes"
     * in a row.
     *
     * @return array
     */
    public function processConfig()
    {
        $backendConfig = $this->parameters['easyadmin.config'];

        $configPasses = array(
            new NormalizerConfigPass(),
            new DesignConfigPass($this->twig, $this->parameters['kernel.debug']),
            new MenuConfigPass(),
            new ActionConfigPass(),
            new MetadataConfigPass($this->doctrine),
            new PropertyConfigPass(),
            new ViewConfigPass(),
            new TemplateConfigPass($this->parameters['kernel.root_dir'].'/Resources/views'),
            new DefaultConfigPass(),
        );

        foreach ($configPasses as $configPass) {
            $backendConfig = $configPass->process($backendConfig);
        }

        return $backendConfig;
    }
}
