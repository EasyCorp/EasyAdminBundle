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

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Normalizes the different configuration formats available for entities, views,
 * actions and properties.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class NormalizerConfigPass implements ConfigPassInterface
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    public function __construct (ContainerBuilder $container)
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

        return $backendConfig;
    }

    /**
     * Transforms the two simple configuration formats into the full expanded
     * configuration. This allows to reuse the same method to process any of the
     * different configuration formats.
     *
     * These are the two simple formats allowed:
     *
     * # Config format #1: no custom entity name
     * easy_admin:
     *     entities:
     *         - AppBundle\Entity\User
     *
     * # Config format #2: simple config with custom entity name
     * easy_admin:
     *     entities:
     *         User: AppBundle\Entity\User
     *
     * And this is the full expanded configuration syntax generated by this method:
     *
     * # Config format #3: expanded entity configuration with 'class' parameter
     * easy_admin:
     *     entities:
     *         User:
     *             class: AppBundle\Entity\User
     *
     * By default the entity name is used as its label (showed in buttons, the
     * main menu, etc.). That's why the config format #3 can optionally define
     * a custom entity label
     *
     * easy_admin:
     *     entities:
     *         User:
     *             class: AppBundle\Entity\User
     *             label: 'Clients'
     *
     * Config format #3 can also define a custom controller.
     *
     * easy_admin:
     *     entities:
     *         User:
     *             class: AppBundle\Entity\User
     *             label: 'Clients'
     *             controller: AppBundle\Admin\UserAdmin
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizeEntityConfig(array $backendConfig)
    {
        $normalizedConfig = array();

        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            // normalize config formats #1 and #2 to use the 'class' option as config format #3
            if (!is_array($entityConfig)) {
                $entityConfig = array('class' => $entityConfig);
            }

            // if config format #3 is used, ensure that it defines the 'class' option
            if (!isset($entityConfig['class'])) {
                throw new \RuntimeException(sprintf('The "%s" entity must define its associated Doctrine entity class using the "class" option.', $entityName));
            }

            // if config format #1 is used, the entity name is the numeric index
            // of the configuration array. In this case, autogenerate the entity
            // name using its class name
            if (is_numeric($entityName)) {
                $entityClassParts = explode('\\', $entityConfig['class']);
                $entityClassName = end($entityClassParts);
                $entityName = $this->getUniqueEntityName($entityClassName, array_keys($normalizedConfig));
            } else {
                // if config format #2 and #3 are used, make sure that the entity
                // name is valid as a PHP method name (this is required to allow
                // extending the backend with a custom controller)
                if (!$this->isValidMethodName($entityName)) {
                    throw new \InvalidArgumentException(sprintf('The name of the "%s" entity contains invalid characters (allowed: letters, numbers, underscores; the first character cannot be a number).', $entityName));
                }
            }

            // if config format #3 defines the 'label' option, use its value.
            // otherwise, use the entity name as its label
            if (!isset($entityConfig['label'])) {
                $entityConfig['label'] = $entityName;
            }

            // if config format #3 defines the 'controller' option, validate
            // that the class derives from the default controller.
            if ( isset($entityConfig['controller'])) {
                if (!$this->isValidAdminController($entityConfig['controller'])) {
                    // TODO - Use an interface
                    throw new \InvalidArgumentException(sprintf('The controller class "%s" is not derived from "JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController".', $entityConfig['controller']));
                }
            }

            $entityConfig['name'] = $entityName;
            $normalizedConfig[$entityName] = $entityConfig;
        }

        $backendConfig['entities'] = $normalizedConfig;

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
     * Checks whether the given string is valid as a PHP method name.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isValidMethodName($name)
    {
        return 0 !== preg_match('/^-?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name);
    }

    /**
     * The name of the entity is included in the URLs of the backend to define
     * the entity used to perform the operations. Obviously, the entity name
     * must be unique to identify entities unequivocally.
     *
     * This method ensures that the given entity name is unique among all the
     * previously existing entities passed as the second argument. This is
     * achieved by iteratively appending a suffix until the entity name is
     * guaranteed to be unique.
     *
     * @param string $entityName
     * @param array  $existingEntityNames
     *
     * @return string The entity name transformed to be unique
     */
    private function getUniqueEntityName($entityName, array $existingEntityNames)
    {
        $uniqueName = $entityName;

        $i = 2;
        while (in_array($uniqueName, $existingEntityNames)) {
            $uniqueName = $entityName.($i++);
        }

        return $uniqueName;
    }

    private function isValidAdminController($class)
    {
        if (0 === strpos($class, '@')) {
            $definition = $this->container->findDefinition(substr($class, 1));
            $class = $definition->getClass();
        }
        
        if ( class_exists($class) ) {
            if (version_compare(phpversion(), '5.3.7', '<')) {
                if (is_subclass_of($class, 'JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController')) {
                    return true;
                }
            }
            else {
                $reflection = new \ReflectionClass($class);
                if ( $reflection->isSubclassOf('JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController')) {
                    return true;
                }
            }
        }
        return false;
    }
}
