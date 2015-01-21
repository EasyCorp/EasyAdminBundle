<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Service;

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
        'datetime' => 'datetime',
        'datetime' => 'date',
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
     * Takes the FQCN of the Doctrine entity and returns all its properties metadata.
     *
     * @param string $entityName Entity FQCN
     *
     * @return array
     */
    private function getEntityPropertiesMetadata($entityClass)
    {
        $entityPropertiesMetadata = array();

        /** @var ClassMetadata $entityMetadata */
        $entityMetadata = $this->em->getMetadataFactory()->getMetadataFor($entityClass);

        // TODO: Check if the entity performs any kind of inheritance: !$entityMetadata->isInheritanceTypeNone()

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
        // are the types of the Doctrine entity property. In that case, replace Doctrine
        // types by Form component types, to avoid errors when rendering the form
        foreach ($entityFields as $fieldName => $fieldConfiguration) {
            $fieldType = $fieldConfiguration['type'];
            $entityFields[$fieldName]['type'] = array_key_exists($fieldType, $this->doctrineTypeToFormTypeMap)
                ? $this->doctrineTypeToFormTypeMap[$fieldType]
                : $fieldType;
        }

        return $entityFields;
    }

    /**
     * These are the entity fields on which the query is performed. For now these
     * fields cannot be configured.
     */
    private function getFieldsForSearchAction(array $entityFields)
    {
        $excludedFieldNames = array();
        $excludedFieldTypes = array('association', 'binary', 'blob', 'date', 'datetime', 'datetimetz', 'guid', 'time', 'object');

        return $this->filterEntityFieldsBasedOnNameAndTypeBlackList($entityFields, $excludedFieldNames, $excludedFieldTypes);
    }

    /**
     * If the backend doesn't define any configuration for the fields of some entity,
     * just create some basic field configuration based on the entity property metadata.
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

    private function filterListFieldsBasedOnSmartGuesses(array $entityFields)
    {
        // empirical guess: listings with more than 8 fields look ugly
        $maxListFields = 8;
        $excludedFieldNames = array('slug', 'password', 'salt', 'updatedAt', 'uuid');
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
