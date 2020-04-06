<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

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
        $backendConfig = $this->processViewConfig($backendConfig);
        $backendConfig = $this->processDefaultFieldsConfig($backendConfig);
        $backendConfig = $this->processFieldConfig($backendConfig);
        $backendConfig = $this->processPageTitleConfig($backendConfig);
        $backendConfig = $this->processMaxResultsConfig($backendConfig);
        $backendConfig = $this->processSortingConfig($backendConfig);

        return $backendConfig;
    }

    private function processViewConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (['edit', 'list', 'new', 'search', 'show'] as $view) {
                // process the 'help' message that each view can define to display it under the page title
                // isset() cannot be used because the value can be 'null' (used to remove the inherited help message)
                if (!\array_key_exists('help', $backendConfig['entities'][$entityName][$view])) {
                    $backendConfig['entities'][$entityName][$view]['help'] = $entityConfig['help'] ?? null;
                }

                // process the 'item_permission' option
                $itemPermission = $entityConfig[$view]['item_permission'] ?? $backendConfig[$view]['item_permission'] ?? null;
                $backendConfig['entities'][$entityName][$view]['item_permission'] = empty($itemPermission) ? null : $itemPermission;
            }
        }

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
            foreach (['edit', 'list', 'new', 'search', 'show'] as $view) {
                if (0 === \count($entityConfig[$view]['fields'])) {
                    $fieldsConfig = $this->filterFieldList(
                        $entityConfig['properties'],
                        $this->getExcludedFieldNames($view, $entityConfig),
                        $this->getExcludedFieldTypes($view),
                        $this->getMaxNumberFields($view)
                    );

                    foreach ($fieldsConfig as $fieldName => $fieldConfig) {
                        if (null === $fieldsConfig[$fieldName]['format']) {
                            $fieldsConfig[$fieldName]['format'] = $this->getFieldFormat($fieldConfig['type'], $backendConfig);
                        }
                    }

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
            foreach (['edit', 'list', 'new', 'search', 'show'] as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    if (!isset($fieldConfig['label']) && isset($fieldConfig['property']) && 'id' === $fieldConfig['property']) {
                        // if the field is called 'id' and doesn't define a custom label, use 'ID' as label to
                        // improve the readability of the label, which is usually related to a primary key
                        $fieldConfig['label'] = 'ID';
                    } elseif (isset($fieldConfig['label']) && false === $fieldConfig['label']) {
                        // if the label is the special value 'false', label must be hidden (use an empty string as the label)
                        $fieldConfig['label'] = '';
                        $fieldConfig['sortable'] = false;
                    } elseif (null === $fieldConfig['label'] && isset($fieldConfig['property']) && 0 !== strpos($fieldConfig['property'], '_easyadmin_form_design_element_')) {
                        // else, generate the label automatically from its name (except if it's a
                        // special element created to render complex forms)
                        $fieldConfig['label'] = $this->humanize($fieldConfig['property']);
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
            foreach (['edit', 'list', 'new', 'search', 'show'] as $view) {
                if (!isset($entityConfig[$view]['title']) && isset($backendConfig[$view]['title'])) {
                    $backendConfig['entities'][$entityName][$view]['title'] = $backendConfig[$view]['title'];
                }
            }
        }

        return $backendConfig;
    }

    /**
     * This method resolves the 'max_results' inheritance when some global view
     * (list, show, etc.) defines a global value for all entities that can be
     * overridden individually by each entity.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processMaxResultsConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (['list', 'search', 'show'] as $view) {
                if (!isset($entityConfig[$view]['max_results']) && isset($backendConfig[$view]['max_results'])) {
                    $backendConfig['entities'][$entityName][$view]['max_results'] = $backendConfig[$view]['max_results'];
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
     *
     * @throws \InvalidArgumentException
     */
    private function processSortingConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (['list', 'search'] as $view) {
                if (!isset($entityConfig[$view]['sort'])) {
                    continue;
                }

                $sortConfig = $entityConfig[$view]['sort'];
                if (!\is_string($sortConfig) && !\is_array($sortConfig)) {
                    throw new \InvalidArgumentException(sprintf('The "sort" option of the "%s" view of the "%s" entity contains an invalid value (it can only be a string or an array).', $view, $entityName));
                }

                if (\is_string($sortConfig)) {
                    $sortConfig = ['field' => $sortConfig, 'direction' => 'DESC'];
                } else {
                    $sortConfig = ['field' => $sortConfig[0], 'direction' => strtoupper($sortConfig[1])];
                }

                if (!\in_array($sortConfig['direction'], ['ASC', 'DESC'])) {
                    throw new \InvalidArgumentException(sprintf('If defined, the second value of the "sort" option of the "%s" view of the "%s" entity can only be "ASC" or "DESC".', $view, $entityName));
                }

                $isSortedByDoctrineAssociation = false !== strpos($sortConfig['field'], '.');
                if (!$isSortedByDoctrineAssociation && (isset($entityConfig[$view]['fields'][$sortConfig['field']]) && true === $entityConfig[$view]['fields'][$sortConfig['field']]['virtual'])) {
                    throw new \InvalidArgumentException(sprintf('The "%s" field cannot be used in the "sort" option of the "%s" view of the "%s" entity because it\'s a virtual property that is not persisted in the database.', $sortConfig['field'], $view, $entityName));
                }

                // sort can be defined using simple properties (sort: author) or association properties (sort: author.name)
                if (substr_count($sortConfig['field'], '.') > 1) {
                    throw new \InvalidArgumentException(sprintf('The "%s" value cannot be used as the "sort" option in the "%s" view of the "%s" entity because it defines multiple sorting levels (e.g. "aaa.bbb.ccc") but only up to one level is supported (e.g. "aaa.bbb").', $sortConfig['field'], $view, $entityName));
                }

                // sort field can be a Doctrine association (sort: author.name) instead of a simple property
                $sortFieldParts = explode('.', $sortConfig['field']);
                $sortFieldProperty = $sortFieldParts[0];

                if (!\array_key_exists($sortFieldProperty, $entityConfig['properties']) && !isset($entityConfig[$view]['fields'][$sortFieldProperty])) {
                    throw new \InvalidArgumentException(sprintf('The "%s" field used in the "sort" option of the "%s" view of the "%s" entity does not exist neither as a property of that entity nor as a virtual field of that view.', $sortFieldProperty, $view, $entityName));
                }

                $backendConfig['entities'][$entityName][$view]['sort'] = $sortConfig;
            }
        }

        return $backendConfig;
    }

    /**
     * Returns the date/time/datetime/number format for the given field
     * according to its type and the default formats defined for the backend.
     *
     * @param string $fieldType
     * @param array  $backendConfig
     *
     * @return string The format that should be applied to the field value
     */
    private function getFieldFormat($fieldType, array $backendConfig)
    {
        if (\in_array($fieldType, ['date', 'date_immutable', 'dateinterval', 'time', 'time_immutable', 'datetime', 'datetime_immutable', 'datetimetz', 'datetimetz_immutable'])) {
            $fieldType = ('_immutable' === mb_substr($fieldType, -10)) ? mb_substr($fieldType, 0, -10) : $fieldType;
            // make 'datetimetz' use the same format as 'datetime'
            $fieldType = ('datetimetz' === $fieldType) ? 'datetime' : $fieldType;

            return $backendConfig['formats'][$fieldType];
        }

        if (\in_array($fieldType, ['bigint', 'integer', 'smallint', 'decimal', 'float'])) {
            return $backendConfig['formats']['number'] ?? null;
        }
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
        $excludedFieldNames = [
            'edit' => [$entityConfig['primary_key_field_name']],
            'list' => ['password', 'salt', 'slug', 'updatedAt', 'uuid'],
            'new' => [$entityConfig['primary_key_field_name']],
            'search' => ['password', 'salt'],
            'show' => [],
        ];

        return $excludedFieldNames[$view] ?? [];
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
        $excludedFieldTypes = [
            'edit' => ['binary', 'blob', 'json_array', 'json', 'object'],
            'list' => ['array', 'binary', 'blob', 'guid', 'json_array', 'json', 'object', 'simple_array', 'text'],
            'new' => ['binary', 'blob', 'json_array', 'json', 'object'],
            'search' => ['association', 'binary', 'boolean', 'blob', 'date', 'date_immutable', 'datetime', 'datetime_immutable', 'dateinterval', 'datetimetz', 'time', 'time_immutable', 'object'],
            'show' => [],
        ];

        return $excludedFieldTypes[$view] ?? [];
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
        $maxNumberFields = [
            'list' => 7,
        ];

        return $maxNumberFields[$view] ?? PHP_INT_MAX;
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
        $filteredFields = [];

        foreach ($fields as $name => $metadata) {
            if (!\in_array($name, $excludedFieldNames, true) && !\in_array($metadata['type'], $excludedFieldTypes, true)) {
                $filteredFields[$name] = $fields[$name];
            }
        }

        if (\count($filteredFields) > $maxNumFields) {
            $filteredFields = \array_slice($filteredFields, 0, $maxNumFields, true);
        }

        return $filteredFields;
    }

    // Copied from Symfony\Component\Form\FormRenderer::humanize()
    // @author Bernhard Schussek <bschussek@gmail.com>
    private function humanize(string $value): string
    {
        return ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $value))));
    }
}
