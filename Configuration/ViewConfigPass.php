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
    private $defaultEntityFields = array();

    private $defaultEntityFieldConfiguration = array(
        'css_class'    => '',    // CSS class or classes applied to form field
        'format'       => null,  // date/time/datetime/number format applied to form field value
        'help'         => null,  // form field help message
        'label'        => null,  // form field label (if 'null', autogenerate it)
        'type'         => null,  // its value matches the value of 'dataType' for list/show and the value of 'fieldType' for new/edit
        'fieldType'    => null,  // Symfony form field type (text, date, number, choice, ...) used to display the field
        'dataType'     => null,  // Data type (text, date, integer, boolean, ...) of the Doctrine property associated with the field
        'virtual'      => false, // is a virtual field or a real Doctrine entity property?
        'sortable'     => true,  // listings can be sorted according to the values of this field
        'template'     => null,  // the path of the template used to render the field in 'show' and 'list' views
        'type_options' => array(), // the options passed to the Symfony Form type used to render the form field
    );

    private $defaultVirtualFieldMetadata = array(
        'columnName' => 'virtual',
        'fieldName' => 'virtual',
        'id' => false,
        'length' => null,
        'nullable' => false,
        'precision' => 0,
        'scale' => 0,
        'type' => 'text',
        'unique' => false,
    );

    private $doctrineTypeToFormTypeMap = array(
        'array' => 'collection',
        'association' => 'entity',
        'bigint' => 'text',
        'blob' => 'textarea',
        'boolean' => 'checkbox',
        'date' => 'date',
        'datetime' => 'datetime',
        'datetimetz' => 'datetime',
        'decimal' => 'number',
        'float' => 'number',
        'guid' => 'text',
        'integer' => 'integer',
        'json_array' => 'textarea',
        'object' => 'textarea',
        'simple_array' => 'collection',
        'smallint' => 'integer',
        'string' => 'text',
        'text' => 'textarea',
        'time' => 'time',
    );

    public function process(array $backendConfiguration)
    {
        $backendConfiguration = $this->processViewConfiguration($backendConfiguration);

        return $backendConfiguration;
    }

    private function processViewConfiguration(array $backendConfiguration)
    {
        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            $this->defaultEntityFields = $this->createFieldsFromEntityProperties($entityConfiguration['properties'], $backendConfiguration);

            $entityConfiguration['list']['fields'] = $this->getFieldsForListView($entityConfiguration, $backendConfiguration);
            $entityConfiguration['show']['fields'] = $this->getFieldsForShowView($entityConfiguration, $backendConfiguration);
            $entityConfiguration['edit']['fields'] = $this->getFieldsForFormBasedViews('edit', $entityConfiguration, $backendConfiguration);
            $entityConfiguration['new']['fields'] = $this->getFieldsForFormBasedViews('new', $entityConfiguration, $backendConfiguration);
            $entityConfiguration['search']['fields'] = $this->getFieldsForSearchAction($entityConfiguration, $backendConfiguration);

            $backendConfiguration['entities'][$entityName] = $entityConfiguration;
        }

        return $backendConfiguration;
    }

    /**
     * Returns the list of fields to show in the 'list' view of this entity.
     *
     * @param array $entityConfiguration
     *
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForListView(array $entityConfiguration, $backendConfiguration)
    {
        if (0 === count($entityConfiguration['list']['fields'])) {
            $maxListFields = 7;
            $excludedFieldNames = array('password', 'salt', 'slug', 'updatedAt', 'uuid');
            $excludedFieldTypes = array('array', 'binary', 'blob', 'guid', 'json_array', 'object', 'simple_array', 'text');

            $entityConfiguration['list']['fields'] = $this->filterListFieldsBasedOnSmartGuesses($this->defaultEntityFields); // $this->filterFieldList($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes, $maxListFields);
        }

        return $this->normalizeFieldsConfiguration('list', $entityConfiguration, $backendConfiguration);
    }

    /**
     * Returns the list of fields to show in the 'show' view of this entity.
     *
     * @param array $entityConfiguration
     *
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForShowView(array $entityConfiguration, $backendConfiguration)
    {
        if (0 === count($entityConfiguration['show']['fields'])) {
            $entityConfiguration['show']['fields'] = $this->defaultEntityFields;
        }

        return $this->normalizeFieldsConfiguration('show', $entityConfiguration, $backendConfiguration);
    }

    /**
     * Returns the list of fields to show in the forms of the given view
     * ('edit' or 'new').
     *
     * @param string $view
     * @param array  $entityConfiguration
     *
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForFormBasedViews($view, array $entityConfiguration, $backendConfiguration)
    {
        if (0 === count($entityConfiguration[$view]['fields'])) {
            $excludedFieldNames = array($entityConfiguration['primary_key_field_name']);
            $excludedFieldTypes = array('binary', 'blob', 'json_array', 'object');
            $entityConfiguration[$view]['fields'] = $this->filterFieldList($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes);
        }

        return $this->normalizeFieldsConfiguration($view, $entityConfiguration, $backendConfiguration);
    }

    /**
     * Returns the list of entity fields on which the search query is performed.
     *
     * @return array The list of fields to use for the search
     */
    private function getFieldsForSearchAction(array $entityConfiguration, $backendConfiguration)
    {
        if (0 === count($entityConfiguration['search']['fields'])) {
            $excludedFieldNames = array();
            $excludedFieldTypes = array('association', 'binary', 'boolean', 'blob', 'date', 'datetime', 'datetimetz', 'time', 'object');
            $entityConfiguration['search']['fields'] = $this->filterFieldList($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes);
        }

        return $this->normalizeFieldsConfiguration('search', $entityConfiguration, $backendConfiguration);
    }

    /**
     * Guesses the best fields to display in a listing when the entity doesn't
     * define any configuration. It does so limiting the number of fields to
     * display and discarding several field types.
     *
     * @param array $entityFields
     *
     * @return array The list of fields to display
     */
    private function filterListFieldsBasedOnSmartGuesses(array $entityFields)
    {
        // empirical guess: listings with more than 7 fields look ugly
        $maxListFields = 7;
        $excludedFieldNames = array('password', 'salt', 'slug', 'updatedAt', 'uuid');
        $excludedFieldTypes = array('array', 'binary', 'blob', 'guid', 'json_array', 'object', 'simple_array', 'text');

        // if the entity has a lot of fields, try to guess which fields we can remove
        $filteredFields = $entityFields;
        foreach ($entityFields as $name => $metadata) {
            if (in_array($name, $excludedFieldNames) || in_array($metadata['type'], $excludedFieldTypes)) {
                unset($filteredFields[$name]);

                // whenever a field is removed, check again if we are below the acceptable number of fields
                if (count($filteredFields) <= $maxListFields) {
                    return $filteredFields;
                }
            }
        }

        // if the entity has still a lot of remaining fields, just slice the last ones
        return array_slice($filteredFields, 0, $maxListFields);
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

    /**
     * Merges all the information about the fields associated with the given view
     * to return the complete set of normalized field configuration.
     *
     * @param string $view
     * @param array  $entityConfiguration
     *
     * @return array The complete field configuration
     */
    private function normalizeFieldsConfiguration($view, $entityConfiguration, $backendConfiguration)
    {
        $configuration = array();
        $fieldsConfiguration = $entityConfiguration[$view]['fields'];
        $originalViewConfiguration = $backendConfiguration['entities'][$entityConfiguration['name']][$view];

        foreach ($fieldsConfiguration as $fieldName => $fieldConfiguration) {
            $originalFieldConfiguration = isset($originalViewConfiguration['fields'][$fieldName]) ? $originalViewConfiguration['fields'][$fieldName] : null;

            if (array_key_exists($fieldName, $entityConfiguration['properties'])) {
                $fieldMetadata = $entityConfiguration['properties'][$fieldName];
            } else {
                // this is a virtual field which doesn't exist as a property of
                // the related entity. That's why Doctrine can't provide metadata for it
                $fieldMetadata = array_merge(
                    $this->defaultVirtualFieldMetadata,
                    array('columnName' => $fieldName, 'fieldName' => $fieldName, 'virtual' => true)
                );
            }

            $normalizedConfiguration = array_replace(
                $this->defaultEntityFieldConfiguration,
                $fieldMetadata,
                $fieldConfiguration
            );

            // virtual fields and associations different from *-to-one cannot be sorted in listings
            $isToManyAssociation = 'association' === $normalizedConfiguration['type']
                && ($normalizedConfiguration['associationType'] & ClassMetadata::TO_MANY);
            if (true === $normalizedConfiguration['virtual'] || $isToManyAssociation) {
                $normalizedConfiguration['sortable'] = false;
            }

            // special case: if the field is called 'id' and doesn't define a custom
            // label, use 'ID' as label. This improves the readability of the label
            // of this important field, which is usually related to the primary key
            if ('id' === $normalizedConfiguration['fieldName'] && !isset($normalizedConfiguration['label'])) {
                $normalizedConfiguration['label'] = 'ID';
            }

            // 'list', 'search' and 'show' views: use the value of the 'type' option
            // as the 'dataType' option because the previous code has already
            // prioritized end-user preferences over Doctrine and default values
            if (in_array($view, array('list', 'search', 'show'))) {
                $normalizedConfiguration['dataType'] = $normalizedConfiguration['type'];
            }

            // 'new' and 'edit' views: if the user has defined the 'type' option
            // for the field, use it as 'fieldType'. Otherwise, infer the best field
            // type using the property data type.
            if (in_array($view, array('edit', 'new'))) {
                $normalizedConfiguration['fieldType'] = isset($originalFieldConfiguration['type'])
                    ? $originalFieldConfiguration['type']
                    : $this->getFormTypeFromDoctrineType($normalizedConfiguration['type']);
            }

            // special case for the 'list' view: 'boolean' properties are displayed
            // as toggleable flip switches when certain conditions are met
            if ('list' === $view && 'boolean' === $normalizedConfiguration['dataType']) {
                // conditions:
                //   1) the end-user hasn't configured the field type explicitly
                //   2) the 'edit' action is enabled for the 'list' view of this entity
                $isEditActionEnabled = array_key_exists('edit', $entityConfiguration['list']['actions']);
                if (!isset($originalFieldConfiguration['type']) && $isEditActionEnabled) {
                    $normalizedConfiguration['dataType'] = 'toggle';
                }
            }

            if (null === $normalizedConfiguration['format']) {
                $normalizedConfiguration['format'] = $this->getFieldFormat($normalizedConfiguration['type'], $backendConfiguration);
            }

            $configuration[$fieldName] = $normalizedConfiguration;
        }

        return $configuration;
    }


    /**
     * If the backend configuration doesn't define any options for the fields of some entity,
     * create some basic field configuration based on the entity's Doctrine metadata.
     *
     * @param array $entityProperties
     *
     * @return array The array of fields
     */
    private function createFieldsFromEntityProperties($entityProperties, $backendConfiguration)
    {
        $fields = array();

        foreach ($entityProperties as $propertyName => $propertyMetadata) {
            $metadata = array_replace($this->defaultEntityFieldConfiguration, $propertyMetadata);
            $metadata['property'] = $propertyName;
            $metadata['dataType'] = $propertyMetadata['type'];
            $metadata['fieldType'] = $this->getFormTypeFromDoctrineType($propertyMetadata['type']);
            $metadata['format'] = $this->getFieldFormat($propertyMetadata['type'], $backendConfiguration);

            $fields[$propertyName] = $metadata;
        }

        return $fields;
    }

    /**
     * Returns the most appropriate Symfony Form type for the given Doctrine type.
     *
     * @param string $doctrineType
     *
     * @return string
     */
    private function getFormTypeFromDoctrineType($doctrineType)
    {
        // don't change this array_key_exists() by isset() because the Doctrine
        // type map can return 'null' values that should be treated like that
        return array_key_exists($doctrineType, $this->doctrineTypeToFormTypeMap)
            ? $this->doctrineTypeToFormTypeMap[$doctrineType]
            : $doctrineType;
    }

    /**
     * Returns the date/time/datetime/number format for the given field
     * according to its type and the default formats defined for the backend.
     *
     * @param string $fieldType
     *
     * @return string The format that should be applied to the field value
     */
    private function getFieldFormat($backendConfiguration, $fieldType)
    {
        if (in_array($fieldType, array('date', 'time', 'datetime', 'datetimetz'))) {
            // make 'datetimetz' use the same format as 'datetime'
            $fieldType = ('datetimetz' === $fieldType) ? 'datetime' : $fieldType;

            return $backendConfiguration['formats'][$fieldType];
        }

        if (in_array($fieldType, array('bigint', 'integer', 'smallint', 'decimal', 'float'))) {
            return isset($backendConfiguration['formats']['number']) ? $backendConfiguration['formats']['number'] : null;
        }
    }
}
