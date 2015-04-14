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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use JavierEguiluz\Bundle\EasyAdminBundle\Reflection\EntityMetadataInspector;
use JavierEguiluz\Bundle\EasyAdminBundle\Reflection\ClassPropertyReflector;

class Configurator
{
    private $backendConfig = array();
    private $entitiesConfig = array();
    private $inspector;
    private $reflector;
    private $defaultEntityFields = array();

    private $defaultEntityFieldConfiguration = array(
        'class'     => null,  // CSS class or classes applied to form field
        'format'    => null,  // date/time/datetime/number format applied to form field value
        'help'      => null,  // form field help message
        'label'     => null,  // form field label (if 'null', autogenerate it)
        'type'      => null,  // its value matches the value of 'dataType' for list/show and the value of 'fieldType' for new/edit
        'fieldType' => null,  // Symfony form field type (text, date, number, choice, ...) used to display the field
        'dataType'  => null,  // Data type (text, date, integer, boolean, ...) of the Doctrine property associated with the field
        'virtual'   => false, // is a virtual field or a real Doctrine entity property?
        'sortable'  => true,  // listings can be sorted according to the values of this field
    );

    private $doctrineTypeToFormTypeMap = array(
        'array' => 'collection',
        'association' => null,
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

    public function __construct(array $backendConfig, EntityMetadataInspector $inspector, ClassPropertyReflector $reflector)
    {
        $this->backendConfig = $backendConfig;
        $this->inspector = $inspector;
        $this->reflector = $reflector;
    }

    /**
     * Processes and returns the full configuration for the given entity name.
     * This configuration includes all the information about the form fields
     * and properties of the entity.
     *
     * @param string $entityName
     *
     * @return array The full entity configuration
     */
    public function getEntityConfiguration($entityName)
    {
        // if the configuration has already been processed for the given entity, reuse it
        if (isset($this->entitiesConfig[$entityName])) {
            return $this->entitiesConfig[$entityName];
        }

        if (!isset($this->backendConfig['entities'][$entityName])) {
            throw new \InvalidArgumentException(sprintf('Entity "%s" is not managed by EasyAdmin.', $entityName));
        }

        $entityConfiguration = $this->backendConfig['entities'][$entityName];

        $entityMetadata = $this->inspector->getEntityMetadata($entityConfiguration['class']);
        $entityConfiguration['primary_key_field_name'] = $entityMetadata->getSingleIdentifierFieldName();

        $entityProperties = $this->processEntityPropertiesMetadata($entityMetadata);
        $entityConfiguration['properties'] = $entityProperties;

        // default fields used when the view (list, edit, etc.) doesn't define its own fields
        $this->defaultEntityFields = $this->createFieldsFromEntityProperties($entityProperties);

        $entityConfiguration['list']['fields'] = $this->getFieldsForListView($entityConfiguration);
        $entityConfiguration['show']['fields'] = $this->getFieldsForShowView($entityConfiguration);
        $entityConfiguration['edit']['fields'] = $this->getFieldsForFormBasedViews('edit', $entityConfiguration);
        $entityConfiguration['new']['fields'] = $this->getFieldsForFormBasedViews('new', $entityConfiguration);
        $entityConfiguration['search']['fields'] = $this->getFieldsForSearchAction();

        $entityConfiguration = $this->introspectGettersAndSetters($entityConfiguration);

        $this->entitiesConfig[$entityName] = $entityConfiguration;

        return $entityConfiguration;
    }

    /**
     * Takes the entity metadata introspected via Doctrine and completes its
     * contents to simplify data processing for the rest of the application.
     *
     * @param ClassMetadata $entityMetadata The entity metadata introspected via Doctrine
     *
     * @return array The entity properties metadata provided by Doctrine
     */
    private function processEntityPropertiesMetadata(ClassMetadata $entityMetadata)
    {
        $entityPropertiesMetadata = array();

        if ($entityMetadata->isIdentifierComposite) {
            throw new \RuntimeException(sprintf("The '%s' entity isn't valid because it contains a composite primary key.", $entityMetadata->name));
        }

        // introspect regular entity fields
        foreach ($entityMetadata->fieldMappings as $fieldName => $fieldMetadata) {
            // field names are tweaked this way to simplify Twig templates and extensions
            $fieldName = str_replace('_', '', $fieldName);

            $entityPropertiesMetadata[$fieldName] = $fieldMetadata;
        }

        // introspect fields for entity associations (except many-to-many)
        foreach ($entityMetadata->associationMappings as $fieldName => $associationMetadata) {
            if (ClassMetadataInfo::MANY_TO_MANY !== $associationMetadata['type']) {
                // field names are tweaked this way to simplify Twig templates and extensions
                $fieldName = str_replace('_', '', $fieldName);

                $entityPropertiesMetadata[$fieldName] = array(
                    'type'            => 'association',
                    'associationType' => $associationMetadata['type'],
                    'fieldName'       => $fieldName,
                    'fetch'           => $associationMetadata['fetch'],
                    'isOwningSide'    => $associationMetadata['isOwningSide'],
                    'targetEntity'    => $associationMetadata['targetEntity'],
                );
            }
        }

        return $entityPropertiesMetadata;
    }

    /**
     * Returns the list of fields to show in the 'list' view of this entity.
     *
     * @param array $entityConfiguration
     *
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForListView(array $entityConfiguration)
    {
        if (0 === count($entityConfiguration['list']['fields'])) {
            $entityConfiguration['list']['fields'] = $this->filterListFieldsBasedOnSmartGuesses($this->defaultEntityFields);
        }

        return $this->normalizeFieldsConfiguration('list', $entityConfiguration);
    }

    /**
     * Returns the list of fields to show in the 'show' view of this entity.
     *
     * @param array $entityConfiguration
     *
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForShowView(array $entityConfiguration)
    {
        if (0 === count($entityConfiguration['show']['fields'])) {
            $entityConfiguration['show']['fields'] = $this->defaultEntityFields;
        }

        return $this->normalizeFieldsConfiguration('show', $entityConfiguration);
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
    protected function getFieldsForFormBasedViews($view, array $entityConfiguration)
    {
        if (0 === count($entityConfiguration[$view]['fields'])) {
            $excludedFieldNames = array($entityConfiguration['primary_key_field_name']);
            $excludedFieldTypes = array('binary', 'blob', 'json_array', 'object');
            $entityConfiguration[$view]['fields'] = $this->filterFieldsByNameAndType($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes);
        }

        return $this->normalizeFieldsConfiguration($view, $entityConfiguration);
    }

    /**
     * Returns the list of entity fields on which the search query is performed.
     *
     * @return array The list of fields to use for the search
     */
    private function getFieldsForSearchAction()
    {
        $excludedFieldNames = array();
        $excludedFieldTypes = array('association', 'binary', 'boolean', 'blob', 'date', 'datetime', 'datetimetz', 'guid', 'time', 'object');

        return $this->filterFieldsByNameAndType($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes);
    }

    /**
     * If the backend configuration doesn't define any options for the fields of some entity,
     * create some basic field configuration based on the entity's Doctrine metadata.
     *
     * @param array $entityProperties
     *
     * @return array The array of fields
     */
    private function createFieldsFromEntityProperties($entityProperties)
    {
        $fields = array();

        foreach ($entityProperties as $propertyName => $propertyMetadata) {
            $metadata = array_replace($this->defaultEntityFieldConfiguration, $propertyMetadata);
            $metadata['property'] = $propertyName;
            $metadata['dataType'] = $propertyMetadata['type'];
            $metadata['fieldType'] = $this->getFormTypeFromDoctrineType($propertyMetadata['type']);
            $metadata['format'] = $this->getFieldFormat($propertyMetadata['type']);

            $fields[$propertyName] = $metadata;
        }

        return $fields;
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

        // if the entity has few fields, show them all
        if (count($entityFields) <= $maxListFields) {
            return $entityFields;
        }

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
     *
     * @return array The filtered list of fields
     */
    private function filterFieldsByNameAndType(array $fields, array $excludedFieldNames, array $excludedFieldTypes)
    {
        $filteredFields = array();

        foreach ($fields as $name => $metadata) {
            if (!in_array($name, $excludedFieldNames) && !in_array($metadata['type'], $excludedFieldTypes)) {
                $filteredFields[$name] = $fields[$name];
            }
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
    private function normalizeFieldsConfiguration($view, $entityConfiguration)
    {
        $configuration = array();
        $fieldsConfiguration = $entityConfiguration[$view]['fields'];
        $originalViewConfiguration = $this->backendConfig['entities'][$entityConfiguration['name']][$view];

        foreach ($fieldsConfiguration as $fieldName => $fieldConfiguration) {
            $originalFieldConfiguration = isset($originalViewConfiguration['fields'][$fieldName]) ? $originalViewConfiguration['fields'][$fieldName] : null;

            if (!array_key_exists($fieldName, $entityConfiguration['properties'])) {
                // treat this field as 'virtual' because it doesn't exist as a
                // property of the related Doctrine entity
                $normalizedConfiguration = array_replace(
                    $this->defaultEntityFieldConfiguration,
                    $fieldConfiguration
                );

                $normalizedConfiguration['virtual'] = true;
            } else {
                // this is a regular field that exists as a property of the related Doctrine entity
                $normalizedConfiguration = array_replace(
                    $this->defaultEntityFieldConfiguration,
                    $entityConfiguration['properties'][$fieldName],
                    $fieldConfiguration
                );
            }

            // virtual fields and associations different from *-to-one cannot be sorted in listings
            $isToManyAssociation = 'association' === $normalizedConfiguration['type']
                && in_array($normalizedConfiguration['associationType'], array(ClassMetadataInfo::ONE_TO_MANY, ClassMetadataInfo::MANY_TO_MANY));
            if (true === $normalizedConfiguration['virtual'] || $isToManyAssociation) {
                $normalizedConfiguration['sortable'] = false;
            }

            // 'list' and 'show' views: use the value of the 'type' option as
            // the 'dataType' option because the previous code has already
            // prioritized end-user preferences over Doctrine and default values
            if (in_array($view, array('list', 'show'))) {
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
                $normalizedConfiguration['format'] = $this->getFieldFormat($normalizedConfiguration['type']);
            }

            $configuration[$fieldName] = $normalizedConfiguration;
        }

        return $configuration;
    }

    /**
     * Returns the date/time/datetime/number format for the given field
     * according to its type and the default formats defined for the backend.
     *
     * @param string $fieldType
     *
     * @return string The format that should be applied to the field value
     */
    private function getFieldFormat($fieldType)
    {
        if (in_array($fieldType, array('date', 'time', 'datetime', 'datetimetz'))) {
            // make 'datetimetz' use the same format as 'datetime'
            $fieldType = ('datetimetz' === $fieldType) ? 'datetime' : $fieldType;

            return $this->backendConfig['formats'][$fieldType];
        }

        if (in_array($fieldType, array('bigint', 'integer', 'smallint', 'decimal', 'float'))) {
            return isset($this->backendConfig['formats']['number']) ? $this->backendConfig['formats']['number'] : null;
        }
    }

    /**
     * Introspects the getters and setters for the fields used by all views.
     * This preprocessing saves a lot of further processing when accessing or
     * setting the value of the entity properties.
     *
     * @param array $entityConfiguration
     *
     * @return array
     */
    private function introspectGettersAndSetters($entityConfiguration)
    {
        foreach (array('new', 'edit', 'list', 'show', 'search') as $view) {
            $fieldsConfiguration = $entityConfiguration[$view]['fields'];

            foreach ($fieldsConfiguration as $fieldName => $fieldConfiguration) {
                $getter = $this->reflector->getGetter($entityConfiguration['class'], $fieldName);
                $fieldConfiguration['getter'] = $getter;

                $setter = $this->reflector->getSetter($entityConfiguration['class'], $fieldName);
                $fieldConfiguration['setter'] = $setter;

                $isPublic = $this->reflector->isPublic($entityConfiguration['class'], $fieldName);
                $fieldConfiguration['isPublic'] = $isPublic;

                $fieldConfiguration['canBeGet'] = $getter || $isPublic;
                $fieldConfiguration['canBeSet'] = $setter || $isPublic;

                $entityConfiguration[$view]['fields'][$fieldName] = $fieldConfiguration;
            }
        }

        return $entityConfiguration;
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
     * Exposes the backend configuration to any external method that needs it.
     *
     * @return array
     */
    public function getBackendConfig()
    {
        return $this->backendConfig;
    }
}
