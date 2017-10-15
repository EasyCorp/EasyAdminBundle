<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Normalizes the different configuration formats available for entities, views,
 * actions and properties.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class NormalizerConfigPass implements ConfigPassInterface
{
    private $defaultViewConfig = array(
        'list' => array(
            'dql_filter' => null,
            'fields' => array(),
        ),
        'search' => array(
            'dql_filter' => null,
            'fields' => array(),
        ),
        'show' => array(
            'fields' => array(),
        ),
        'form' => array(
            'fields' => array(),
            'form_options' => array(),
        ),
        'edit' => array(
            'fields' => array(),
            'form_options' => array(),
        ),
        'new' => array(
            'fields' => array(),
            'form_options' => array(),
        ),
    );

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->normalizeEntityConfig($backendConfig);
        $backendConfig = $this->normalizeViewConfig($backendConfig);
        $backendConfig = $this->normalizePropertyConfig($backendConfig);
        $backendConfig = $this->normalizeFormDesignConfig($backendConfig);
        $backendConfig = $this->normalizeActionConfig($backendConfig);
        $backendConfig = $this->normalizeFormConfig($backendConfig);
        $backendConfig = $this->normalizeControllerConfig($backendConfig);
        $backendConfig = $this->normalizeTranslationConfig($backendConfig);

        return $backendConfig;
    }

    /**
     * By default the entity name is used as its label (showed in buttons, the
     * main menu, etc.) unless the entity config defines the 'label' option:.
     *
     * easy_admin:
     *     entities:
     *         User:
     *             class: AppBundle\Entity\User
     *             label: 'Clients'
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizeEntityConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            if (!isset($entityConfig['label'])) {
                $backendConfig['entities'][$entityName]['label'] = $entityName;
            }
        }

        return $backendConfig;
    }

    /**
     * Process the configuration of the 'form' view (if any) to complete the
     * configuration of the 'edit' and 'new' views.
     *
     * @param array $backendConfig [description]
     *
     * @return array
     */
    private function normalizeFormConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            if (isset($entityConfig['form'])) {
                $entityConfig['new'] = isset($entityConfig['new']) ? $this->mergeFormConfig($entityConfig['form'], $entityConfig['new']) : $entityConfig['form'];
                $entityConfig['edit'] = isset($entityConfig['edit']) ? $this->mergeFormConfig($entityConfig['form'], $entityConfig['edit']) : $entityConfig['form'];
            }

            $backendConfig['entities'][$entityName] = $entityConfig;
        }

        return $backendConfig;
    }

    /**
     * Normalizes the view configuration when some of them doesn't define any
     * configuration.
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizeViewConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            // if the original 'search' config doesn't define its own DQL filter, use the one form 'list'
            if (!isset($entityConfig['search']) || !array_key_exists('dql_filter', $entityConfig['search'])) {
                $entityConfig['search']['dql_filter'] = isset($entityConfig['list']['dql_filter']) ? $entityConfig['list']['dql_filter'] : null;
            }

            foreach (array('edit', 'form', 'list', 'new', 'search', 'show') as $view) {
                $entityConfig[$view] = array_replace_recursive(
                    $this->defaultViewConfig[$view],
                    isset($entityConfig[$view]) ? $entityConfig[$view] : array()
                );
            }

            $backendConfig['entities'][$entityName] = $entityConfig;
        }

        return $backendConfig;
    }

    /**
     * Fields can be defined using two different formats:.
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
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizePropertyConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $designElementIndex = 0;
            foreach (array('form', 'edit', 'list', 'new', 'search', 'show') as $view) {
                $fields = array();
                foreach ($entityConfig[$view]['fields'] as $i => $field) {
                    if (!is_string($field) && !is_array($field)) {
                        throw new \RuntimeException(sprintf('The values of the "fields" option for the "%s" view of the "%s" entity can only be strings or arrays.', $view, $entityConfig['class']));
                    }

                    if (is_string($field)) {
                        // Config format #1: field is just a string representing the entity property
                        $fieldConfig = array('property' => $field);
                    } else {
                        // Config format #1: field is an array that defines one or more
                        // options. Check that either 'property' or 'type' option is set
                        if (!array_key_exists('property', $field) && !array_key_exists('type', $field)) {
                            throw new \RuntimeException(sprintf('One of the values of the "fields" option for the "%s" view of the "%s" entity does not define neither of the mandatory options ("property" or "type").', $view, $entityConfig['class']));
                        }

                        $fieldConfig = $field;
                    }

                    // for 'image' type fields, if the entity defines an 'image_base_path'
                    // option, but the field does not, use the value defined by the entity
                    if (isset($fieldConfig['type']) && 'image' === $fieldConfig['type']) {
                        if (!isset($fieldConfig['base_path']) && isset($entityConfig['image_base_path'])) {
                            $fieldConfig['base_path'] = $entityConfig['image_base_path'];
                        }
                    }

                    // for 'file' type fields, if the entity defines an 'file_base_path'
                    // option, but the field does not, use the value defined by the entity
                    if (isset($fieldConfig['type']) && 'file' === $fieldConfig['type']) {
                        if (!isset($fieldConfig['base_path']) && isset($entityConfig['file_base_path'])) {
                            $fieldConfig['base_path'] = $entityConfig['file_base_path'];
                        }
                    }

                    // fields that don't define the 'property' name are special form design elements
                    $fieldName = isset($fieldConfig['property']) ? $fieldConfig['property'] : '_easyadmin_form_design_element_'.$designElementIndex;
                    $fields[$fieldName] = $fieldConfig;
                    ++$designElementIndex;
                }

                $backendConfig['entities'][$entityName][$view]['fields'] = $fields;
            }
        }

        return $backendConfig;
    }

    /**
     * Normalizes the configuration of the special elements that forms may include
     * to create advanced designs (such as dividers and fieldsets).
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizeFormDesignConfig(array $backendConfig)
    {
        // edge case: if the first 'group' type is not the first form field,
        // all the previous form fields are "ungrouped". To avoid design issues,
        // insert an empty 'group' type (no label, no icon) as the first form element.
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('form', 'edit', 'new') as $view) {
                $fieldNumber = 0;
                $isTheFirstGroupElement = true;

                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    ++$fieldNumber;
                    if (!isset($fieldConfig['property']) && isset($fieldConfig['type']) && 'group' === $fieldConfig['type']) {
                        if ($isTheFirstGroupElement && $fieldNumber > 1) {
                            $backendConfig['entities'][$entityName][$view]['fields'] = array_merge(
                                array('_easyadmin_form_design_element_forced_first_group' => array('type' => 'group')),
                                $backendConfig['entities'][$entityName][$view]['fields']
                            );

                            break;
                        }

                        $isTheFirstGroupElement = false;
                    }
                }
            }
        }

        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('form', 'edit', 'new') as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    // this is a form design element instead of a regular property
                    $isFormDesignElement = !isset($fieldConfig['property']) && isset($fieldConfig['type']);
                    if ($isFormDesignElement && in_array($fieldConfig['type'], array('divider', 'group', 'section'))) {
                        // assign them a property name to add them later as unmapped form fields
                        $fieldConfig['property'] = $fieldName;

                        // transform the form type shortcuts into the real form type short names
                        $fieldConfig['type'] = 'easyadmin_'.$fieldConfig['type'];
                    }

                    $backendConfig['entities'][$entityName][$view]['fields'][$fieldName] = $fieldConfig;
                }
            }
        }

        return $backendConfig;
    }

    private function normalizeActionConfig(array $backendConfig)
    {
        $views = array('edit', 'list', 'new', 'show', 'form');

        foreach ($views as $view) {
            if (!isset($backendConfig[$view]['actions'])) {
                $backendConfig[$view]['actions'] = array();
            }

            // there is no need to check if the "actions" option for the global
            // view is an array because it's done by the Configuration definition
        }

        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($views as $view) {
                if (!isset($entityConfig[$view]['actions'])) {
                    $backendConfig['entities'][$entityName][$view]['actions'] = array();
                }

                if (!is_array($backendConfig['entities'][$entityName][$view]['actions'])) {
                    throw new \InvalidArgumentException(sprintf('The "actions" configuration for the "%s" view of the "%s" entity must be an array (a string was provided).', $view, $entityName));
                }
            }
        }

        return $backendConfig;
    }

    /**
     * It processes the optional 'controller' config option to check if the
     * given controller exists (it doesn't matter if it's a normal controller
     * or if it's defined as a service).
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizeControllerConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            if (isset($entityConfig['controller'])) {
                $controller = trim($entityConfig['controller']);

                if (!$this->container->has($controller) && !class_exists($controller)) {
                    throw new \InvalidArgumentException(sprintf('The "%s" value defined in the "controller" option of the "%s" entity is not a valid controller. For a regular controller, set its FQCN as the value; for a controller defined as service, set its service name as the value.', $controller, $entityName));
                }

                $backendConfig['entities'][$entityName]['controller'] = $controller;
            }
        }

        return $backendConfig;
    }

    private function normalizeTranslationConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            if (!isset($entityConfig['translation_domain'])) {
                $entityConfig['translation_domain'] = $backendConfig['translation_domain'];
            }

            if ('' === $entityConfig['translation_domain']) {
                throw new \InvalidArgumentException(sprintf('The value defined in the "translation_domain" option of the "%s" entity is not a valid translation domain name (use false to disable translations).', $entityName));
            }

            $backendConfig['entities'][$entityName] = $entityConfig;
        }

        return $backendConfig;
    }

    /**
     * Merges the form configuration recursively from the 'form' view to the
     * 'edit' and 'new' views. It processes the configuration of the form fields
     * in a special way to keep all their configuration and allow overriding and
     * removing of fields.
     *
     * @param array $parentConfig The config of the 'form' view
     * @param array $childConfig  The config of the 'edit' and 'new' views
     *
     * @return array
     */
    private function mergeFormConfig(array $parentConfig, array $childConfig)
    {
        // save the fields config for later processing
        $parentFields = isset($parentConfig['fields']) ? $parentConfig['fields'] : array();
        $childFields = isset($childConfig['fields']) ? $childConfig['fields'] : array();
        $removedFieldNames = $this->getRemovedFieldNames($childFields);

        // first, perform a recursive replace to merge both configs
        $mergedConfig = array_replace_recursive($parentConfig, $childConfig);

        // merge the config of each field individually
        $mergedFields = array();
        foreach ($parentFields as $parentFieldName => $parentFieldConfig) {
            if (isset($parentFieldConfig['property']) && in_array($parentFieldConfig['property'], $removedFieldNames)) {
                continue;
            }

            if (!isset($parentFieldConfig['property'])) {
                // this isn't a regular form field but a special design element (group, section, divider); add it
                $mergedFields[$parentFieldName] = $parentFieldConfig;
                continue;
            }

            $childFieldConfig = $this->findFieldConfigByProperty($childFields, $parentFieldConfig['property']) ?: array();
            $mergedFields[$parentFieldName] = array_replace_recursive($parentFieldConfig, $childFieldConfig);
        }

        // add back the fields that are defined in child config but not in parent config
        foreach ($childFields as $childFieldName => $childFieldConfig) {
            $isFormDesignElement = !isset($childFieldConfig['property']);
            $isNotRemovedField = isset($childFieldConfig['property']) && '-' !== substr($childFieldConfig['property'], 0, 1);
            $isNotAlreadyIncluded = isset($childFieldConfig['property']) && !in_array($childFieldConfig['property'], array_keys($mergedFields));

            if ($isFormDesignElement || ($isNotRemovedField && $isNotAlreadyIncluded)) {
                $mergedFields[$childFieldName] = $childFieldConfig;
            }
        }

        // finally, copy the processed field config into the merged config
        $mergedConfig['fields'] = $mergedFields;

        return $mergedConfig;
    }

    /**
     * The 'edit' and 'new' views can remove fields defined in the 'form' view
     * by defining fields with a '-' dash at the beginning of its name (e.g.
     * { property: '-name' } to remove the 'name' property).
     *
     * @param array $fieldsConfig
     *
     * @return array
     */
    private function getRemovedFieldNames(array $fieldsConfig)
    {
        $removedFieldNames = array();
        foreach ($fieldsConfig as $fieldConfig) {
            if (isset($fieldConfig['property']) && '-' === substr($fieldConfig['property'], 0, 1)) {
                $removedFieldNames[] = substr($fieldConfig['property'], 1);
            }
        }

        return $removedFieldNames;
    }

    private function findFieldConfigByProperty(array $fieldsConfig, $propertyName)
    {
        foreach ($fieldsConfig as $fieldConfig) {
            if (isset($fieldConfig['property']) && $propertyName === $fieldConfig['property']) {
                return $fieldConfig;
            }
        }

        return null;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Configuration\NormalizerConfigPass', 'JavierEguiluz\Bundle\EasyAdminBundle\Configuration\NormalizerConfigPass', false);
