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

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Initializes the configuration for all the views of each entity, which is
 * needed when some entity relies on the default configuration for some view.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ViewConfigPass implements ConfigPassInterface
{
    public function process(array $backendConfiguration)
    {
        $backendConfiguration = $this->processViewConfiguration($backendConfiguration);
        $backendConfiguration = $this->processFieldConfiguration($backendConfiguration);

        return $backendConfiguration;
    }

    /**
     * This method takes care of the views that don't define their fields. In
     * those cases, we just use the $entityConfig['properties'] information and
     * we filter some fields to improve the user experience for default config.
     */
    private function processViewConfiguration(array $backendConfiguration)
    {
        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                if (0 === count($entityConfiguration[$view]['fields'])) {
                    $fieldsConfiguration = $this->filterFieldList(
                        $entityConfiguration['properties'],
                        $this->getExcludedFieldNames($view, $entityConfiguration),
                        $this->getExcludedFieldTypes($view),
                        $this->getMaxNumberFields($view)
                    );

                    $backendConfiguration['entities'][$entityName][$view]['fields'] = $fieldsConfiguration;
                }
            }
        }

        return $backendConfiguration;
    }

    /**
     * This methods makes some minor tweaks in fields configuration to improve
     * the user experience.
     */
    private function processFieldConfiguration(array $backendConfiguration)
    {
        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                foreach ($entityConfiguration[$view]['fields'] as $fieldName => $fieldConfiguration) {
                    // special case: if the field is called 'id' and doesn't define a custom
                    // label, use 'ID' as label. This improves the readability of the label
                    // of this important field, which is usually related to the primary key
                    if ('id' === $fieldConfiguration['fieldName'] && !isset($fieldConfiguration['label'])) {
                        $fieldConfiguration['label'] = 'ID';
                    }

                    // special case for the 'list' view: 'boolean' properties are displayed
                    // as toggleable flip switches when certain conditions are met
                    if ('list' === $view && 'boolean' === $fieldConfiguration['dataType']) {
                        // conditions:
                        //   1) the end-user hasn't configured the field type explicitly
                        //   2) the 'edit' action is enabled for the 'list' view of this entity
                        // $originalViewConfiguration = $entityConfiguration[$view];
                        // $originalFieldConfiguration = isset($originalViewConfiguration['fields'][$fieldName]) ? $originalViewConfiguration['fields'][$fieldName] : null;
                        $isEditActionEnabled = array_key_exists('edit', $entityConfiguration['list']['actions']);
                        if (!isset($fieldConfiguration['type']) && $isEditActionEnabled) {
                            $fieldConfiguration['dataType'] = 'toggle';
                        }
                    }

                    $backendConfiguration['entities'][$entityName][$view]['fields'][$fieldName] = $fieldConfiguration;
                }
            }
        }

        return $backendConfiguration;
    }

    private function getExcludedFieldNames($view, $entityConfiguration)
    {
        $excludedFieldNames = array(
            'edit' => array($entityConfiguration['primary_key_field_name']),
            'list' => array('password', 'salt', 'slug', 'updatedAt', 'uuid'),
            'new' => array($entityConfiguration['primary_key_field_name']),
            'search' => array('password', 'salt'),
            'show' => array(),
        );

        return isset($excludedFieldNames[$view]) ? $excludedFieldNames[$view] : null;
    }

    private function getExcludedFieldTypes($view)
    {
        $excludedFieldTypes = array(
            'edit' => array('binary', 'blob', 'json_array', 'object'),
            'list' => array('array', 'binary', 'blob', 'guid', 'json_array', 'object', 'simple_array', 'text'),
            'new' => array('binary', 'blob', 'json_array', 'object'),
            'search' => array('association', 'binary', 'boolean', 'blob', 'date', 'datetime', 'datetimetz', 'time', 'object'),
            'show' => array(),
        );

        return isset($excludedFieldTypes[$view]) ? $excludedFieldTypes[$view] : null;
    }

    private function getMaxNumberFields($view)
    {
        $maxNumberFields = array(
            'edit' => null,
            'list' => 7,
            'new' => null,
            'search' => null,
            'show' => null,
        );

        return isset($maxNumberFields[$view]) ? $maxNumberFields[$view] : null;
    }

    /**
     * Filters a list of fields excluding the given list of field names and field types.
     *
     * @param array    $fields
     * @param string[] $excludedFieldNames
     * @param string[] $excludedFieldTypes
     * @param int      $maxNumFields
     *
     * @return array The filtered list of fields
     */
    private function filterFieldList(array $fields, array $excludedFieldNames = array(), array $excludedFieldTypes = array(), $maxNumFields = null)
    {
        $filteredFields = array();

        foreach ($fields as $name => $metadata) {
            if (!in_array($name, $excludedFieldNames) && !in_array($metadata['type'], $excludedFieldTypes)) {
                $filteredFields[$name] = $fields[$name];
            }
        }

        if (null !== $maxNumFields) {
            $filteredFields = array_slice($filteredFields, 0, $maxNumFields, true);
        }

        return $filteredFields;
    }
}
