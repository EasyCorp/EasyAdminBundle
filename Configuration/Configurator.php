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

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use JavierEguiluz\Bundle\EasyAdminBundle\Reflection\ClassPropertyReflector;

class Configurator
{
    private $backendConfig = array();
    private $entitiesConfig = array();
    private $doctrineManager;
    private $reflector;
    private $defaultEntityFields = array();

    private $defaultEntityFieldConfiguration = array(
        'class'     => null,   // CSS class/classes
        'format'    => null,   // date/time/datetime/number format
        'help'      => null,   // form field help message
        'label'     => null,   // form field label (if 'null', autogenerate it)
        'type'      => null,   // multi-purpose variable used by both dataType and fieldType
        'fieldType' => null,   // Symfony form field type (text, date, number, choice, ...)
        'dataType'  => null,   // Doctrine property data type (text, date, integer, boolean, ...)
        'virtual'   => false,  // is a virtual field or a real entity property?
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

    public function __construct(array $backendConfig, ManagerRegistry $manager, ClassPropertyReflector $reflector)
    {
        $this->backendConfig = $backendConfig;
        $this->doctrineManager = $manager;
        $this->reflector = $reflector;
    }

    /**
     * Processes and returns the full configuration for the given entity name.
     * This configuration includes all the information about the form fields
     * and properties of the entity.
     *
     * @param  string $entityName
     * @return array  The full entity configuration
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

        $entityClass = $entityConfiguration['class'];
        $em = $this->doctrineManager->getManagerForClass($entityClass);
        $doctrineEntityMetadata = $em->getMetadataFactory()->getMetadataFor($entityClass);

        $entityConfiguration['primary_key_field_name'] = $doctrineEntityMetadata->getSingleIdentifierFieldName();

        $entityProperties = $this->getEntityPropertiesMetadata($doctrineEntityMetadata);
        $entityConfiguration['properties'] = $entityProperties;

        // default fields used when the action (list, edit, etc.) doesn't define its own fields
        $this->defaultEntityFields = $this->createEntityFieldsFromEntityProperties($entityProperties);

        $entityConfiguration['list']['fields'] = $this->getFieldsForListAction($entityConfiguration);
        $entityConfiguration['show']['fields'] = $this->getFieldsForShowAction($entityConfiguration);
        $entityConfiguration['edit']['fields'] = $this->getFieldsForFormBasedActions('edit', $entityConfiguration);
        $entityConfiguration['new']['fields'] = $this->getFieldsForFormBasedActions('new', $entityConfiguration);
        $entityConfiguration['search']['fields'] = $this->getFieldsForSearchAction($entityConfiguration);

        $entityConfiguration = $this->introspectGettersAndSetters($entityConfiguration);

        $this->entitiesConfig[$entityName] = $entityConfiguration;

        return $entityConfiguration;
    }

    /**
     * Takes the entity metadata introspected via Doctrine and completes its
     * contents to simplify data processing for the rest of the application.
     *
     * @param  ClassMetadata $entityMetadata The entity metadata introspected via Doctrine
     * @return array         The entity properties metadata provided by Doctrine
     */
    private function getEntityPropertiesMetadata(ClassMetadata $entityMetadata)
    {
        $entityPropertiesMetadata = array();

        if ($entityMetadata->isIdentifierComposite) {
            throw new \RuntimeException(sprintf("The '%s' entity isn't valid because it contains a composite primary key.", $entityMetadata->name));
        }

        // introspect regular entity fields
        foreach ($entityMetadata->fieldMappings as $fieldName => $fieldMetadata) {
            $fieldMetadata['dataType'] = $fieldMetadata['type'];
            $fieldMetadata['fieldType'] = $fieldMetadata['type'];

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
                    'dataType'        => 'association',
                    'fieldType'       => 'association',
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
     * Returns the list of fields to show in the 'list' action of this entity.
     *
     * @param  array $entityConfiguration
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForListAction(array $entityConfiguration)
    {
        $entityFields = array();

        // there is a custom configuration for 'list' fields
        if (count($entityConfiguration['list']['fields']) > 0) {
            return $this->normalizeFieldsConfiguration('list', $entityConfiguration);
        }

        return $this->filterListFieldsBasedOnSmartGuesses($this->defaultEntityFields);
    }

    /**
     * Returns the list of fields to show in the 'show' action of this entity.
     *
     * @param  array $entityConfiguration
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForShowAction(array $entityConfiguration)
    {
        // there is a custom configuration for 'show' fields
        if (count($entityConfiguration['show']['fields']) > 0) {
            return $this->normalizeFieldsConfiguration('show', $entityConfiguration);
        }

        return $this->defaultEntityFields;
    }

    /**
     * Returns the list of fields to show in the forms of this entity for the
     * actions which display forms ('edit' and 'new').
     *
     * @param  array $entityConfiguration
     * @return array The list of fields to show and their metadata
     */
    protected function getFieldsForFormBasedActions($action, array $entityConfiguration)
    {
        $entityFields = array();

        // there is a custom field configuration for this action
        if (count($entityConfiguration[$action]['fields']) > 0) {
            $entityFields = $this->normalizeFieldsConfiguration($action, $entityConfiguration);
        } else {
            $excludedFieldNames = array($entityConfiguration['primary_key_field_name']);
            $excludedFieldTypes = array('binary', 'blob', 'json_array', 'object');
            $entityFields = $this->filterFieldsByNameAndType($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes);
        }

        // if the user has defined the 'type' for the field, use it. Otherwise,
        // guess the best form type using the Doctrine type associated with the field
        foreach ($entityFields as $fieldName => $fieldConfiguration) {
            if (!isset($entityConfiguration[$action]['fields'][$fieldName]['type'])) {
                $fieldType = $fieldConfiguration['type'];

                // don't change this array_key_exists() by isset() because the Doctrine
                // type map can return 'null' values that shouldn't be ignored
                $entityFields[$fieldName]['fieldType'] = array_key_exists($fieldType, $this->doctrineTypeToFormTypeMap)
                    ? $this->doctrineTypeToFormTypeMap[$fieldType]
                    : 'text';
            }
        }

        return $entityFields;
    }

    /**
     * Returns the list of entity fields on which the search query is performed.
     *
     * @param  array $entityConfiguration
     * @return array The list of fields to use for the search
     */
    private function getFieldsForSearchAction(array $entityConfiguration)
    {
        $excludedFieldNames = array();
        $excludedFieldTypes = array('association', 'binary', 'blob', 'date', 'datetime', 'datetimetz', 'guid', 'time', 'object');

        return $this->filterFieldsByNameAndType($this->defaultEntityFields, $excludedFieldNames, $excludedFieldTypes);
    }

    /**
     * If the backend configuration doesn't define any options for the fields of some entity,
     * create some basic field configuration based on the entity's Doctrine metadata.
     *
     * @param  array $entityProperties
     * @return array The array of entity fields
     */
    private function createEntityFieldsFromEntityProperties($entityProperties)
    {
        $entityFields = array();

        foreach ($entityProperties as $propertyName => $propertyMetadata) {
            $metadata = array_replace($this->defaultEntityFieldConfiguration, $propertyMetadata);
            $metadata['property'] = $propertyName;
            $metadata['format'] = $this->getFieldFormat($metadata['dataType']);

            $entityFields[$propertyName] = $metadata;
        }

        return $entityFields;
    }

    /**
     * Guesses the best fields to display in a listing when the entity doesn't
     * define any configuration. It does so limiting the number of fields to
     * display and discarding several field types.
     *
     * @param  array $entityFields
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
     * @param  array $fields
     * @param  array $excludedFieldNames
     * @param  array $excludedFieldTypes
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
     * This method takes the default field configuration, the Doctrine's entity
     * metadata and the configured field options to merge and process them all
     * and generate the final and complete field configuration.
     *
     * @param  string $action
     * @param  array  $entityConfiguration
     * @return array  The complete field configuration
     */
    private function normalizeFieldsConfiguration($action, $entityConfiguration)
    {
        $configuration = array();
        $fieldsConfiguration = $entityConfiguration[$action]['fields'];

        foreach ($fieldsConfiguration as $fieldName => $fieldConfiguration) {
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

            // for the 'list' and 'show' actions, the 'type' field configuration
            // corresponds to the 'dataType' option, so we copy its value
            if (in_array($action, array('list', 'show'))) {
                $normalizedConfiguration['dataType'] = $normalizedConfiguration['type'];
            }
            // for the 'new' and 'edit' actions, the 'type' field configuration
            // corresponds to the 'fieldType' option, so we copy its value
            if (in_array($action, array('list', 'show'))) {
                $normalizedConfiguration['fieldType'] = $normalizedConfiguration['type'];
            }

            // for the 'list' action, 'boolean' properties are displayed as toggleable flip switches
            // unless its type is explicitly configured
            if ('list' === $action && 'boolean' === $normalizedConfiguration['dataType'] && !isset($fieldConfiguration['type'])) {
                $normalizedConfiguration['dataType'] = 'toggle';
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
     * @param  string $fieldType
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
     * Introspects the getters and setters for the fields used by all actions.
     * This preprocessing saves a lot of further processing when accessing or
     * setting the value of the entity properties.
     *
     * @param  array $entityConfiguration
     * @return array
     */
    private function introspectGettersAndSetters($entityConfiguration)
    {
        foreach (array('new', 'edit', 'list', 'show', 'search') as $action) {
            $fieldsConfiguration = $entityConfiguration[$action]['fields'];

            foreach ($fieldsConfiguration as $fieldName => $fieldConfiguration) {
                $getter = $this->reflector->getGetter($entityConfiguration['class'], $fieldName);
                $fieldConfiguration['getter'] = $getter;

                $setter = $this->reflector->getSetter($entityConfiguration['class'], $fieldName);
                $fieldConfiguration['setter'] = $setter;

                $isPublic = $this->reflector->isPublic($entityConfiguration['class'], $fieldName);
                $fieldConfiguration['isPublic'] = $isPublic;

                $fieldConfiguration['canBeGet'] = $getter || $isPublic;
                $fieldConfiguration['canBeSet'] = $setter || $isPublic;

                $entityConfiguration[$action]['fields'][$fieldName] = $fieldConfiguration;
            }
        }

        return $entityConfiguration;
    }
}
