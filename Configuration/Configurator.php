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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class Configurator
{
    private $backendConfig = array();
    private $entitiesConfig = array();
    private $em;

    private $doctrineTypeToFormTypeMap = array(
        'association' => null,
        'array' => 'collection',
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

    public function __construct(array $backendConfig, ObjectManager $em)
    {
        $this->backendConfig = $backendConfig;
        $this->em = $em;
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
        if (array_key_exists($entityName, $this->entitiesConfig)) {
            return $this->entitiesConfig[$entityName];
        }

        $entityConfiguration = array();

        $entityConfiguration['name'] = $entityName;

        $entityClass = $this->backendConfig['entities'][$entityName]['class'];
        $entityConfiguration['class'] = $entityClass;

        $entityProperties = $this->getEntityPropertiesMetadata($entityClass);
        $entityConfiguration['properties'] = $entityProperties;

        $entityConfiguration['list']['fields'] = $this->getFieldsForListAction($this->backendConfig['entities'][$entityName], $entityProperties);
        $entityConfiguration['edit']['fields'] = $this->getFieldsForFormBasedActions('edit', $this->backendConfig['entities'][$entityName], $entityProperties);
        $entityConfiguration['new']['fields'] = $this->getFieldsForFormBasedActions('new', $this->backendConfig['entities'][$entityName], $entityProperties);
        $entityConfiguration['search']['fields'] = $this->getFieldsForSearchAction($entityProperties);

        $this->entitiesConfig[$entityName] = $entityConfiguration;

        return $entityConfiguration;
    }

    /**
     * Takes the FQCN of the entity and returns all the metadata of its properties
     * introspected via Doctrine.
     *
     * @param  string $entityClass The fully qualified class name of the entity
     * @return array  The entity properties metadata provided by Doctrine
     */
    private function getEntityPropertiesMetadata($entityClass)
    {
        $entityPropertiesMetadata = array();

        /** @var ClassMetadata $entityMetadata */
        $entityMetadata = $this->em->getMetadataFactory()->getMetadataFor($entityClass);

        if ('id' !== $entityMetadata->getSingleIdentifierFieldName()) {
            throw new \RuntimeException(sprintf("The '%s' entity isn't valid because it doesn't define a primary key called 'id'.", $entityClass));
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
                $entityPropertiesMetadata[$fieldName] = array(
                    'association'  => $associationMetadata['type'],
                    'fieldName'    => $fieldName,
                    'fetch'        => $associationMetadata['fetch'],
                    'isOwningSide' => $associationMetadata['isOwningSide'],
                    'type'         => 'association',
                    'targetEntity' => $associationMetadata['targetEntity'],
                );
            }
        }

        return $entityPropertiesMetadata;
    }

    /**
     * Returns the list of fields to show in the listings of this entity.
     *
     * @param  array $entityConfiguration
     * @param  array $entityProperties
     * @return array The list of fields to show and their metadata
     */
    private function getFieldsForListAction(array $entityConfiguration, array $entityProperties)
    {
        $entityFields = array();

        // there is a custom configuration for 'list' fields
        if (count($entityConfiguration['list']['fields']) > 0) {
            return $this->filterEntityFieldsBasedOnConfiguration($entityProperties, $entityConfiguration['list']['fields']);
        }

        $entityFields = $this->createEntityFieldsFromEntityProperties($entityProperties);

        return $this->filterListFieldsBasedOnSmartGuesses($entityFields);
    }

    /**
     * Returns the list of fields to show in the forms of this entity for the
     * actions which display forms ('edit' and 'new').
     *
     * @param  array $entityConfiguration
     * @param  array $entityProperties
     * @return array The list of fields to show and their metadata
     */
    protected function getFieldsForFormBasedActions($action, array $entityConfiguration, array $entityProperties)
    {
        $entityFields = array();

        // there is a custom field configuration for this action
        if (count($entityConfiguration[$action]['fields']) > 0) {
            $entityFields = $this->filterEntityFieldsBasedOnConfiguration($entityProperties, $entityConfiguration[$action]['fields']);
        // there is a custom field configuration for the common and special 'form' action
        } elseif (count($entityConfiguration['form']['fields']) > 0) {
            $entityFields = $this->filterEntityFieldsBasedOnConfiguration($entityProperties, $entityConfiguration['form']['fields']);
        } else {
            $entityFields = $this->createEntityFieldsFromEntityProperties($entityProperties);

            $excludedFieldNames = array('id');
            $excludedFieldTypes = array('binary', 'blob', 'json_array', 'object');
            $entityFields = $this->filterEntityFieldsBasedOnNameAndTypeBlackList($entityFields, $excludedFieldNames, $excludedFieldTypes);
        }

        // for entities which don't define their field configuration, the field types
        // are the types of the Doctrine entity property. To avoid errors when rendering
        // the form, replace Doctrine types by Form component types
        foreach ($entityFields as $fieldName => $fieldConfiguration) {
            $fieldType = $fieldConfiguration['type'];
            $entityFields[$fieldName]['type'] = array_key_exists($fieldType, $this->doctrineTypeToFormTypeMap)
                ? $this->doctrineTypeToFormTypeMap[$fieldType]
                : $fieldType;
        }

        return $entityFields;
    }

    /**
     * Returns the list of entity fields on which the search query is performed.
     *
     * @param  array $entityFields
     * @return array The list of fields to use for the search
     */
    private function getFieldsForSearchAction(array $entityFields)
    {
        $excludedFieldNames = array();
        $excludedFieldTypes = array('association', 'binary', 'blob', 'date', 'datetime', 'datetimetz', 'guid', 'time', 'object');

        return $this->filterEntityFieldsBasedOnNameAndTypeBlackList($entityFields, $excludedFieldNames, $excludedFieldTypes);
    }

    /**
     * If the backend configuration doesn't define any options for the fields of some entity,
     * create some basic field configuration based on the entity property metadata.
     *
     * @param  array $entityProperties
     * @return array The array of entity fields
     */
    private function createEntityFieldsFromEntityProperties($entityProperties)
    {
        $entityFields = array();

        foreach ($entityProperties as $propertyName => $propertyMetadata) {
            $entityFields[$propertyName] = array_replace($propertyMetadata, array(
                'property' => $propertyName,
                'type'     => $propertyMetadata['type'],
                'class'    => null,
                'help'     => null,
                'label'    => null,
                'virtual'  => false,
            ));
        }

        return $entityFields;
    }

    /**
     * Combines the entity properties metadata with the entity fields configuration
     * to produce the list of fields that should be displayed. It's used when the
     * backend explicitly configures the list of fields to display in a listing or
     * a form.
     *
     * @param  array $entityProperties
     * @param  array $configuredFields
     * @return array The list of fields to show and their configuration
     */
    private function filterEntityFieldsBasedOnConfiguration(array $entityProperties, array $configuredFields)
    {
        $filteredFields = array();

        foreach ($configuredFields as $fieldName => $fieldConfiguration) {
            if (array_key_exists($fieldName, $entityProperties)) {
                $filteredFields[$fieldName] = array_replace($fieldConfiguration, $entityProperties[$fieldName]);
                $filteredFields[$fieldName]['virtual'] = false;
            } else {
                // these fields aren't real entity properties but methods. they are
                // used to display 'virtual fields' based on entity methods
                // e.g. public function getFullName() { return $this->name.' '.$this->surname; }
                $filteredFields[$fieldName] = $fieldConfiguration;
                $filteredFields[$fieldName]['virtual'] = true;
            }

            if (null === $fieldConfiguration['type']) {
                if (array_key_exists($fieldName, $entityProperties)) {
                    $filteredFields[$fieldName]['type'] = $entityProperties[$fieldName]['type'];
                }
            } else {
                $filteredFields[$fieldName]['type'] = $fieldConfiguration['type'];
            }

            $filteredFields[$fieldName]['label'] = array_key_exists('label', $fieldConfiguration) ? $fieldConfiguration['label'] : null;
            $filteredFields[$fieldName]['help'] = array_key_exists('help', $fieldConfiguration) ? $fieldConfiguration['help'] : null;
            $filteredFields[$fieldName]['class'] = array_key_exists('class', $fieldConfiguration) ? $fieldConfiguration['class'] : null;
        }

        return $filteredFields;
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
        // empirical guess: listings with more than 8 fields look ugly
        $maxListFields = 8;
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
     * Filters the given list of properties to remove the field types and field
     * names passed as arguments.
     *
     * @param  array $entityFields
     * @param  array $fieldNameBlackList
     * @param  array $fieldTypeBlackList
     * @return array The filtered list of files
     */
    private function filterEntityFieldsBasedOnNameAndTypeBlackList(array $entityFields, array $fieldNameBlackList, array $fieldTypeBlackList)
    {
        $filteredFields = array();

        foreach ($entityFields as $name => $metadata) {
            if (!in_array($name, $fieldNameBlackList) && !in_array($metadata['type'], $fieldTypeBlackList)) {
                $filteredFields[$name] = $entityFields[$name];
            }
        }

        return $filteredFields;
    }
}
