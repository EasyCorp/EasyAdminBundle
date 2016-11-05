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
 * Initializes the configuration for all the views of each entity, which is
 * needed when some entity relies on the default configuration for some view.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ViewConfigPass implements ConfigPassInterface
{
    public function process(array $backendConfig)
    {
        $backendConfig = $this->processDefaultFieldsConfig($backendConfig);
        $backendConfig = $this->processFieldConfig($backendConfig);
        $backendConfig = $this->processPageTitleConfig($backendConfig);
        $backendConfig = $this->processSortingConfig($backendConfig);

        return $backendConfig;
    }

    /**
     * This method takes care of the views that don't define their fields. In
     * those cases, we just use the $entityConfig['properties'] information and
     * we filter some fields to improve the user experience for default config.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processDefaultFieldsConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                if (0 === count($entityConfig[$view]['fields'])) {
                    $fieldsConfig = $this->filterFieldList(
                        $entityConfig['properties'],
                        $this->getExcludedFieldNames($view, $entityConfig),
                        $this->getExcludedFieldTypes($view),
                        $this->getMaxNumberFields($view)
                    );

                    $backendConfig['entities'][$entityName][$view]['fields'] = $fieldsConfig;
                }
            }
        }

        return $backendConfig;
    }

    /**
     * This methods makes some minor tweaks in fields configuration to improve
     * the user experience.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processFieldConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    // special case: if the field is called 'id' and doesn't define a custom
                    // label, use 'ID' as label. This improves the readability of the label
                    // of this important field, which is usually related to the primary key
                    if ('id' === $fieldConfig['fieldName'] && !isset($fieldConfig['label'])) {
                        $fieldConfig['label'] = 'ID';
                    }

                    $backendConfig['entities'][$entityName][$view]['fields'][$fieldName] = $fieldConfig;
                }
            }
        }

        return $backendConfig;
    }

    /**
     * This method resolves the page title inheritance when some global view
     * (list, edit, etc.) defines a global title for all entities that can be
     * overridden individually by each entity.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processPageTitleConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('edit', 'list', 'new', 'show') as $view) {
                if (!isset($entityConfig[$view]['title']) && isset($backendConfig[$view]['title'])) {
                    $backendConfig['entities'][$entityName][$view]['title'] = $backendConfig[$view]['title'];
                }
            }
        }

        return $backendConfig;
    }

    /**
     * This method processes the optional 'sort' config that the 'list' and
     * 'search' views can define to override the default (id, DESC) sorting
     * applied to their contents.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processSortingConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('list', 'search') as $view) {
                if (!isset($entityConfig[$view]['sort'])) {
                    continue;
                }

                $sortConfig = $entityConfig[$view]['sort'];
                if (!is_string($sortConfig) && !is_array($sortConfig)) {
                    throw new \InvalidArgumentException(sprintf('The "sort" option of the "%s" view of the "%s" entity contains an invalid value (it can only be a string or an array).', $view, $entityName));
                }

                if (is_string($sortConfig)) {
                    $sortConfig = array('field' => $sortConfig, 'direction' => 'DESC');
                } else {
                    $sortConfig = array('field' => $sortConfig[0], 'direction' => strtoupper($sortConfig[1]));
                }

                if (!in_array($sortConfig['direction'], array('ASC', 'DESC'))) {
                    throw new \InvalidArgumentException(sprintf('If defined, the second value of the "sort" option of the "%s" view of the "%s" entity can only be "ASC" or "DESC".', $view, $entityName));
                }

                if (isset($entityConfig[$view]['fields'][$sortConfig['field']]) && true === $entityConfig[$view]['fields'][$sortConfig['field']]['virtual']) {
                    throw new \InvalidArgumentException(sprintf('The "%s" field cannot be used in the "sort" option of the "%s" view of the "%s" entity because it\'s a virtual property that is not persisted in the database.', $sortConfig['field'], $view, $entityName));
                }

                if (!array_key_exists($sortConfig['field'], $entityConfig['properties']) && !isset($entityConfig[$view]['fields'][$sortConfig['field']])) {
                    throw new \InvalidArgumentException(sprintf('The "%s" field used in the "sort" option of the "%s" view of the "%s" entity does not exist neither as a property of that entity nor as a virtual field of that view.', $sortConfig['field'], $view, $entityName));
                }

                $backendConfig['entities'][$entityName][$view]['sort'] = $sortConfig;
            }
        }

        return $backendConfig;
    }

    /**
     * Returns the list of excluded field names for the given view.
     *
     * @param string $view
     * @param array  $entityConfig
     *
     * @return array
     */
    private function getExcludedFieldNames($view, array $entityConfig)
    {
        $excludedFieldNames = array(
            'edit' => array($entityConfig['primary_key_field_name']),
            'list' => array('password', 'salt', 'slug', 'updatedAt', 'uuid'),
            'new' => array($entityConfig['primary_key_field_name']),
            'search' => array('password', 'salt'),
            'show' => array(),
        );

        return isset($excludedFieldNames[$view]) ? $excludedFieldNames[$view] : array();
    }

    /**
     * Returns the list of excluded field types for the given view.
     *
     * @param string $view
     *
     * @return array
     */
    private function getExcludedFieldTypes($view)
    {
        $excludedFieldTypes = array(
            'edit' => array('binary', 'blob', 'json_array', 'object'),
            'list' => array('array', 'binary', 'blob', 'guid', 'json_array', 'object', 'simple_array', 'text'),
            'new' => array('binary', 'blob', 'json_array', 'object'),
            'search' => array('association', 'binary', 'boolean', 'blob', 'date', 'datetime', 'datetimetz', 'time', 'object'),
            'show' => array(),
        );

        return isset($excludedFieldTypes[$view]) ? $excludedFieldTypes[$view] : array();
    }

    /**
     * Returns the maximum number of fields to display be default for the
     * given view.
     *
     * @param string $view
     *
     * @return int
     */
    private function getMaxNumberFields($view)
    {
        $maxNumberFields = array(
            'list' => 7,
        );

        return isset($maxNumberFields[$view]) ? $maxNumberFields[$view] : PHP_INT_MAX;
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
    private function filterFieldList(array $fields, array $excludedFieldNames, array $excludedFieldTypes, $maxNumFields)
    {
        $filteredFields = array();

        foreach ($fields as $name => $metadata) {
            if (!in_array($name, $excludedFieldNames) && !in_array($metadata['type'], $excludedFieldTypes)) {
                $filteredFields[$name] = $fields[$name];
            }
        }

        if (count($filteredFields) > $maxNumFields) {
            $filteredFields = array_slice($filteredFields, 0, $maxNumFields, true);
        }

        return $filteredFields;
    }
}
