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
 * Views can define their fields using two different formats:
 *
 * # Config format #1: simple configuration
 * easy_admin:
 *     Client:
 *         # ...
 *         list:
 *             fields: ['id', 'name', 'email']
 *
 * # Config format #2: extended configuration
 * easy_admin:
 *     Client:
 *         # ...
 *         list:
 *             fields: ['id', 'name', { property: 'email', label: 'Contact' }]
 *
 * This method processes both formats to produce a common form field configuration
 * format used in the rest of the application.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class PropertyNormalizer implements NormalizerInterface
{
    public function normalize(array $backendConfiguration)
    {
        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                $fields = array();
                foreach ($entityConfiguration[$view]['fields'] as $field) {
                    if (!is_string($field) && !is_array($field)) {
                        throw new \RuntimeException(sprintf('The values of the "fields" option for the "%s" view of the "%s" entity can only be strings or arrays.', $view, $entityConfiguration['class']));
                    }

                    if (is_string($field)) {
                        // Config format #1: field is just a string representing the entity property
                        $fieldConfiguration = array('property' => $field);
                    } else {
                        // Config format #1: field is an array that defines one or more
                        // options. Check that the mandatory 'property' option is set
                        if (!array_key_exists('property', $field)) {
                            throw new \RuntimeException(sprintf('One of the values of the "fields" option for the "%s" view of the "%s" entity does not define the "property" option.', $view, $entityConfiguration['class']));
                        }

                        $fieldConfiguration = $field;
                    }

                    // for 'image' type fields, if the entity defines an 'image_base_path'
                    // option, but the field does not, use the value defined by the entity
                    if (isset($fieldConfiguration['type']) && 'image' === $fieldConfiguration['type']) {
                        if (!isset($fieldConfiguration['base_path']) && isset($entityConfiguration['image_base_path'])) {
                            $fieldConfiguration['base_path'] = $entityConfiguration['image_base_path'];
                        }
                    }

                    $fieldName = $fieldConfiguration['property'];
                    $fields[$fieldName] = $fieldConfiguration;
                }

                $backendConfiguration['entities'][$entityName][$view]['fields'] = $fields;
            }
        }

        return $backendConfiguration;
    }
}
