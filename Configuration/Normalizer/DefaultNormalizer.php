<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Normalizer;

/**
 * Processes default values for some backend configuration options.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class DefaultNormalizer implements NormalizerInterface
{
    public function normalize(array $backendConfiguration)
    {
        $entityNames = array_keys($backendConfiguration['entities']);
        $firstEntityName = isset($entityNames[0]) ? $entityNames[0] : null;

        // this option is used to redirect the homepage of the backend to the
        // 'list' view of the first configured entity.
        $backendConfiguration['default_entity_name'] = $firstEntityName;

        return $backendConfiguration;
    }
}
