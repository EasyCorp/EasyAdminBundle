<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

class EasyAdminExtension extends Extension
{
    private $defaultBundleConfiguration = array(
        'site_name' => 'EasyAdmin',
        'list_max_results' => 15,
        'list_actions' => array('edit'),
        'entities' => array(),
    );

    private $defaultFieldConfiguration = array(
        'class'  => null,
        'help'   => null,
        'label'  => null,
        'type'   => null,
        'format' => null,
    );

    public function load(array $configs, ContainerBuilder $container)
    {
        // process bundle's configuration parameters
        $bundleConfiguration = $this->processConfiguration(new Configuration(), $configs);

        $backendConfiguration = array_replace($this->defaultBundleConfiguration, $bundleConfiguration);
        $backendConfiguration['entities'] = $this->processEntitiesConfiguration($backendConfiguration['entities']);

        $container->setParameter('easyadmin.config', $backendConfiguration);

        // load bundle's services
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * Processes, normalizes and initializes the configuration of the entities
     * that are managed by the backend. Several configuration formats are allowed,
     * so this method normalizes them all.
     *
     * @param  array $entitiesConfiguration
     * @return array The configured entities
     * @throws InvalidConfigurationException
     */
    private function processEntitiesConfiguration(array $entitiesConfiguration)
    {
        if (0 === count($entitiesConfiguration)) {
            return $entitiesConfiguration;
        }

        $entities = array();

        $entityNames = array();
        foreach ($entitiesConfiguration as $entityName => $entityConfiguration) {

            if (is_string($entityConfiguration)) {
                // Allows to specify only the FQCN as entity parameter
                $entityConfiguration = array('class' => $entityConfiguration);
            }

            if (is_array($entityConfiguration) && !isset($entityConfiguration['class'])) {
                // If the user specifies a hash/array, then the "class" param must be defined
                throw new InvalidConfigurationException('The entity class must be defined.');
            }

            if (is_numeric($entityName)) {
                // If the users specifies a simple array, we try to search in the "name", "label" and "class" attributes.
                if (isset($entityConfiguration['name'])) {
                    $entityName = $entityConfiguration['name'];
                } elseif (isset($entityConfiguration['label'])) {
                    $entityName = $entityConfiguration['label'];
                } else {
                    $entityClassParts = explode('\\', $entityConfiguration['class']);
                    $entityName = array_pop($entityClassParts);
                }
            }

            // copy the original entity configuration to not loose any of its options
            $config = $entityConfiguration;

            // basic entity configuration
            $config['label'] = $entityName;
            $config['class'] = $entityConfiguration['class'];

            // configuration for the actions related to the entity ('list', 'edit', etc.)
            foreach (array('edit', 'form', 'list', 'new', 'show') as $action) {
                // if needed, initialize options to simplify further configuration processing
                if (!array_key_exists($action, $config)) {
                    $config[$action] = array('fields' => array());
                }
                if (!array_key_exists('fields', $config[$action])) {
                    $config[$action]['fields'] = array();
                }

                $config[$action]['fields'] = $this->processFieldsConfiguration($config[$action]['fields'], $action, $config['class']);
            }

            $uniqueEntityName = $this->getUniqueEntityName($config['class'], $entityNames);
            $entityNames[] = $uniqueEntityName;

            $config['name']  = $uniqueEntityName;

            $entities[$uniqueEntityName] = $config;
        }

        return $entities;
    }

    /**
     * The name of the entity is used in the URLs of the application to define the
     * entity which should be used for each action. Obviously, the entity name
     * must be unique in the application.
     *
     * To avoid entity name conflicts when two different entities are called the
     * same, this method modifies the entity name if necessary to ensure that is
     * unique.
     *
     * @param  string $entityClass
     * @param  array  $entityNames The list of names of all the managed entities
     * @return string The name of the entity guaranteed to be unique in the application
     */
    private function getUniqueEntityName($entityClass, $entityNames)
    {
        $entityClassParts = explode('\\', $entityClass);
        $uniqueEntityName = array_pop($entityClassParts);

        while (in_array($uniqueEntityName, $entityNames)) {
            $uniqueEntityName .= '_';
        }

        return $uniqueEntityName;
    }

    /**
     * Actions can define their fields using two different formats:
     *
     * # simple configuration
     * easy_admin:
     *     Client:
     *         # ...
     *         list:
     *             fields: ['id', 'name', 'email']
     *
     * # extended configuration
     * easy_admin:
     *     Client:
     *         # ...
     *         list:
     *             fields: ['id', 'name', { property: 'email', label: 'Contact' }]
     *
     * This method processes both formats to produce a common form field configuration
     * format. It also initializes and adds some default form field options to simplify
     * field configuration processing in other methods and templates.
     *
     * @param  array  $fieldsConfiguration
     * @param  string $action
     * @param  string $entityClass
     * @return array  The configured entity fields
     */
    private function processFieldsConfiguration(array $fieldsConfiguration, $action, $entityClass)
    {
        $fields = array();

        foreach ($fieldsConfiguration as $field) {
            // simple configuration: field is just a string representing the entity property
            if (is_string($field)) {
                $fieldConfiguration = array(
                    'property' => $field,
                );
            // extended configuration: field is an array that defines one or more options.
            // related entity property is configured via the mandatory 'property' option.
            } elseif (is_array($field)) {
                if (!array_key_exists('property', $field)) {
                    throw new \RuntimeException(sprintf('One of the values of the "fields" option for the "%s" action of the "%s" entity does not define the "property" option.', $action, $entityClass));
                }

                $fieldConfiguration = $field;
            } else {
                throw new \RuntimeException(sprintf('The values of the "fields" option for the "$s" action of the "%s" entity can only be strings or arrays.', $action, $entityClass));
            }

            $fieldConfiguration = array_replace($this->defaultFieldConfiguration, $fieldConfiguration);

            $fieldName = $fieldConfiguration['property'];
            $fields[$fieldName] = $fieldConfiguration;
        }

        return $fields;
    }
}
