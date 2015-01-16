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

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EasyAdminExtension extends Extension
{
    private $defaultConfigOptions = array(
        'site_name' => 'ACME',
        'list_max_results' => 15,
        'list_actions' => array('edit'),
        'entities' => array(),
    );

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $options = array_replace($this->defaultConfigOptions, $config);
        $options['entities'] = $this->processEntityConfiguration($options['entities']);

        $container->setParameter('easy_admin.config', $options);
    }

    protected function processEntityConfiguration(array $entitiesConfiguration)
    {
        if (0 === count($entitiesConfiguration)) {
            return $entitiesConfiguration;
        }

        $entitiesConfigurationKeys = array_keys($entitiesConfiguration);
        if (is_integer($entitiesConfigurationKeys[0])) {
            return $this->processEntityConfigurationFromSimpleParameters($entitiesConfiguration);
        }

        return $this->processEntityConfigurationFromComplexParameters($entitiesConfiguration);
    }

    private function processEntityConfigurationFromSimpleParameters(array $config)
    {
        $entities = array();
        foreach ($config as $entityClass) {
            $parts = explode('\\', $entityClass);
            $entityName = array_pop($parts);

            $entities[$entityName] = array(
                'label' => $entityName,
                'name'  => $entityName,
                'class' => $entityClass,
            );
        }

        return $entities;
    }

    private function processEntityConfigurationFromComplexParameters(array $config)
    {
        $entities = array();
        foreach ($config as $customEntityName => $entityConfiguration) {
            $parts = explode('\\', $entityConfiguration['class']);
            $realEntityName = array_pop($parts);

            // copy the original entity to not loose any of its configuration
            $entities[$realEntityName] = $config[$customEntityName];

            // process the original configuration to use the format needed by the application
            $entities[$realEntityName]['label'] = $customEntityName;
            $entities[$realEntityName]['name']  = $realEntityName;
            $entities[$realEntityName]['class'] = $entityConfiguration['class'];
        }

        return $entities;
    }
}
