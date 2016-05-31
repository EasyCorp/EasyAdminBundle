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
        $backendConfig = $this->normalizeFormViewConfig($backendConfig);
        $backendConfig = $this->normalizeViewConfig($backendConfig);
        $backendConfig = $this->normalizePropertyConfig($backendConfig);
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
    private function normalizeFormViewConfig(array $backendConfig)
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
                foreach ($entityConfig[$view]['fields'] as $field) {
                    if (!is_string($field) && !is_array($field)) {
                        throw new \RuntimeException(sprintf('The values of the "fields" option for the "%s" view of the "%s" entity can only be strings or arrays.', $view, $entityConfig['class']));
                    }

                    if (is_string($field)) {
                        // Config format #1: field is just a string representing the entity property
                        $fieldConfig = array('property' => $field);
                    } else {
                        // Config format #1: field is an array that defines one or more
                        // options. Check that the mandatory 'property' option is set
                        if (!array_key_exists('property', $field)) {
                            throw new \RuntimeException(sprintf('One of the values of the "fields" option for the "%s" view of the "%s" entity does not define the "property" option.', $view, $entityConfig['class']));
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

                    $fieldName = $fieldConfig['property'];
                    $fields[$fieldName] = $fieldConfig;
                }

                $backendConfig['entities'][$entityName][$view]['fields'] = $fields;
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
