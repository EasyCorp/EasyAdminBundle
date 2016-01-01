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

/**
 * Entities can define a special 'form' view to set the options of 'edit' and
 * 'new' views simultaneously. This normalizer merges all these configurations
 * to create the full definitive configuration of 'edit' and 'new' views.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class FormViewConfigPass implements ConfigPassInterface
{
    public function process(array $backendConfiguration)
    {
        $entitiesConfiguration = $backendConfiguration['entities'];

        foreach ($entitiesConfiguration as $entityName => $entityConfiguration) {
            // copy the original entity configuration to not lose any of its options
            $config = $entityConfiguration;

            if (isset($config['form'])) {
                $config['new'] = isset($config['new']) ? array_replace($config['form'], $config['new']) : $config['form'];
                $config['edit'] = isset($config['edit']) ? array_replace($config['form'], $config['edit']) : $config['form'];
            }

            $entitiesConfiguration[$entityName] = $config;
        }

        $backendConfiguration['entities'] = $entitiesConfiguration;

        return $backendConfiguration;
    }
}
