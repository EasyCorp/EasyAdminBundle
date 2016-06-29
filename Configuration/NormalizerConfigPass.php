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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Normalizes the different configuration formats available for entities, views,
 * actions and properties.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class NormalizerConfigPass implements ConfigPassInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(array $backendConfig)
    {
        $backendConfig = $this->normalizeEntityConfig($backendConfig);
        $backendConfig = $this->normalizeFormConfig($backendConfig);
        $backendConfig = $this->normalizeViewConfig($backendConfig);
        $backendConfig = $this->normalizePropertyConfig($backendConfig);
        $backendConfig = $this->normalizeFormDesignConfig($backendConfig);
        $backendConfig = $this->normalizeActionConfig($backendConfig);
        $backendConfig = $this->normalizeControllerConfig($backendConfig);

        return $backendConfig;
    }

    /**
     * By default the entity name is used as its label (showed in buttons, the
     * main menu, etc.) unless the entity config defines the 'label' option:
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
                $entityConfig['new'] = isset($entityConfig['new']) ? array_replace($entityConfig['form'], $entityConfig['new']) : $entityConfig['form'];
                $entityConfig['edit'] = isset($entityConfig['edit']) ? array_replace($entityConfig['form'], $entityConfig['edit']) : $entityConfig['form'];
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
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
                if (!isset($entityConfig[$view])) {
                    $entityConfig[$view] = array('fields' => array());
                }

                if (!isset($entityConfig[$view]['fields'])) {
                    $entityConfig[$view]['fields'] = array();
                }

                if (in_array($view, array('edit', 'new')) && !isset($entityConfig[$view]['form_options'])) {
                    $entityConfig[$view]['form_options'] = array();
                }
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
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
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

                    $fieldName = isset($fieldConfig['property']) ? $fieldConfig['property'] : 'field_'.$i;
                    $fields[$fieldName] = $fieldConfig;
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
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('edit', 'new') as $view) {
                $realFormfields = array();
                $designElementNumber = 0;
                $currentFormGroup = null;

                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    // this is a form design element instead of a regular property
                    if (!isset($fieldConfig['property']) && isset($fieldConfig['type'])) {
                        // 'group' form elements are not transformed into form types
                        // the trick is to assign this group as an option to the form fields defined after this group
                        if ('group' === $fieldConfig['type']) {
                            // a 'group' element defines the form group where the
                            // following form fields are displayed (until a new 'group' is found)
                            $currentFormGroup = sprintf('_easyadmin_form_group_%s', ++$designElementNumber);

                            continue;
                        }

                        // 'divider' and 'section' elements are transformed into special form types
                        if (in_array($fieldConfig['type'], array('divider', 'section'))) {
                            // assign them a random property name (they are later added as unmapped form fields)
                            $fieldConfig['property'] = sprintf('_easyadmin_form_design_element_%s_%d', $fieldConfig['type'], ++$designElementNumber);

                            // transform the form type shortcuts into the real form type short names
                            $fieldConfig['type'] = 'easyadmin_'.$fieldConfig['type'];
                        }
                    }

                    $fieldConfig['form_group'] = $currentFormGroup;

                    $realFormfields[$fieldName] = $fieldConfig;
                }

                $backendConfig['entities'][$entityName][$view]['fields'] = $realFormfields;
            }
        }

        return $backendConfig;
    }

    private function normalizeActionConfig(array $backendConfig)
    {
        $views = array('edit', 'list', 'new', 'show');

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
}
