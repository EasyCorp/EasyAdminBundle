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
 * Initializes the configuration for all the views of each entity, which is
 * needed when some entity relies on the default configuration for some view.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ViewNormalizer implements NormalizerInterface
{
    public function normalize(array $backendConfiguration)
    {
        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                if (!isset($entityConfiguration[$view])) {
                    $entityConfiguration[$view] = array('fields' => array());
                }

                if (!isset($entityConfiguration[$view]['fields'])) {
                    $entityConfiguration[$view]['fields'] = array();
                }

                if (in_array($view, array('edit', 'new')) && !isset($entityConfiguration[$view]['form_options'])) {
                    $entityConfiguration[$view]['form_options'] = array();
                }
            }

            $backendConfiguration['entities'][$entityName] = $entityConfiguration;
        }

        return $backendConfiguration;
    }
}
