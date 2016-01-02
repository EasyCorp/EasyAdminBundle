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
 * Processes the entity fields to complete their configuration and to treat
 * some fields in a special way.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class PropertyConfigPass implements ConfigPassInterface
{
    private $defaultEntityFieldConfiguration = array(
        // CSS class or classes applied to form field
        'css_class' => '',
        // date/time/datetime/number format applied to form field value
        'format' => null,
        // form field help message
        'help' => null,
        // form field label (if 'null', autogenerate it)
        'label' => null,
        // its value matches the value of 'dataType' for list/show and the value of 'fieldType' for new/edit
        'type' => null,
        // Symfony form field type (text, date, number, choice, ...) used to display the field
        'fieldType' => null,
        // Data type (text, date, integer, boolean, ...) of the Doctrine property associated with the field
        'dataType' => null,
        // is a virtual field or a real Doctrine entity property?
        'virtual' => false,
        // listings can be sorted according to the values of this field
        'sortable' => true,
        // the path of the template used to render the field in 'show' and 'list' views
        'template' => null,
        // the options passed to the Symfony Form type used to render the form field
        'type_options' => array(),
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
        $backendConfiguration = $this->processFieldConfiguration($backendConfiguration);

        return $backendConfiguration;
    }

    private function processFieldConfiguration(array $backendConfiguration)
    {
        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                //$configuration = array();
                $fieldsConfiguration = $entityConfiguration[$view]['fields'];
                $originalViewConfiguration = $backendConfiguration['entities'][$entityName][$view];

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

                    //$configuration[$fieldName] = $normalizedConfiguration;
                    $backendConfiguration['entities'][$entityName][$view]['fields'][$fieldName] = $normalizedConfiguration;
//echo "<pre>";var_dump(array_keys($configuration));
                }
//echo "<pre>";var_dump($configuration);exit;
  //              $backendConfiguration['entities'][$entityName][$view]['fields'] = $configuration;
            }
        }

        return $backendConfiguration;
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
