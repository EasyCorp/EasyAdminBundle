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

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

class EasyAdminExtension extends Extension
{
    private $defaultActionConfiguration = array(
        'name'   => null,     // the name of the controller method or application route (depending on the 'type')
        'type'   => 'method', // 'method' if the action is a controller method; 'route' if it's an application route
        'label'  => null,     // action label (displayed as link or button) (if 'null', autogenerate it)
        'class'  => '',       // the CSS class applied to the button/link displayed by the action
        'icon'   => null,     // the name of the FontAwesome icon to display next to the 'label' (don't include the 'fa-' prefix)
    );

    public function load(array $configs, ContainerBuilder $container)
    {
        // process bundle's configuration parameters
        $backendConfiguration = $this->processConfiguration(new Configuration(), $configs);
        $backendConfiguration['entities'] = $this->getEntitiesConfiguration($backendConfiguration['entities']);
        $backendConfiguration = $this->processEntityActions($backendConfiguration);

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
     * @return array The full entity configuration
     */
    public function getEntitiesConfiguration(array $entitiesConfiguration)
    {
        if (0 === count($entitiesConfiguration)) {
            return $entitiesConfiguration;
        }

        $configuration = $this->normalizeEntitiesConfiguration($entitiesConfiguration);
        $configuration = $this->processEntitiesConfiguration($configuration);

        return $configuration;
    }

    /**
     * Transforms the two simple configuration formats into the full expanded
     * configuration. This allows to reuse the same method to process any of the
     * different configuration formats.
     *
     * These are the two simple formats allowed:
     *
     * # Config format #1: no custom entity label
     * easy_admin:
     *     entities:
     *         - AppBundle\Entity\User
     *
     * # Config format #2: simple config with custom entity label
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
     * # Config format #3 can optionally define a custom entity label
     * easy_admin:
     *     entities:
     *         User:
     *             class: AppBundle\Entity\User
     *             label: 'Clients'
     *
     * @param  array $entitiesConfiguration The entity configuration in one of the simplified formats
     * @return array The normalized configuration
     */
    private function normalizeEntitiesConfiguration(array $entitiesConfiguration)
    {
        $normalizedConfiguration = array();

        foreach ($entitiesConfiguration as $entityLabel => $entityConfiguration) {
            // config formats #1 and #2
            if (!is_array($entityConfiguration)) {
                $entityConfiguration = array('class' => $entityConfiguration);
            }

            $entityClassParts = explode('\\', $entityConfiguration['class']);
            $entityClassName = end($entityClassParts);

            # if config format #3 defines the 'label' option, use its value.
            # otherwise, infer the entity label from its configuration.
            if (!isset($entityConfiguration['label'])) {
                // config format #1 doesn't define any entity label because configuration is
                // just a plain numeric array (the label is the integer key of that array).
                // In that case, use the entity class name as its label
                $entityConfiguration['label'] = is_integer($entityLabel) ? $entityClassName : $entityLabel;
            }

            $entityName = $this->getUniqueEntityName($entityClassName, array_keys($normalizedConfiguration));
            $entityConfiguration['name'] = $entityName;

            $normalizedConfiguration[$entityName] = $entityConfiguration;
        }

        return $normalizedConfiguration;
    }

    /**
     * Merges all the actions that can be configured in the backend and normalize
     * them to get the final actions related to each entity view.
     *
     * @param  array  $backendConfiguration
     * @return array
     */
    public function processEntityActions(array $backendConfiguration)
    {
        $entitiesConfiguration = array();

        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach (array('edit', 'list', 'new', 'show') as $view) {
                $defaultActions = $this->getDefaultActions($view);
                $backendActions = isset($backendConfiguration[$view]['actions']) ? $backendConfiguration[$view]['actions'] : array();
                $backendActions = $this->normalizeActionsConfiguration($backendActions);

                $viewActions = array_replace($defaultActions, $backendActions);
                $viewActions = $this->filterRemovedActions($viewActions);

                $entityActions = isset($entityConfiguration[$view]['actions']) ? $entityConfiguration[$view]['actions'] : array();
                $entityActions = $this->normalizeActionsConfiguration($entityActions);

                $actions = array_replace($viewActions, $entityActions);
                $actions = $this->filterRemovedActions($actions);

                $entityConfiguration[$view]['actions'] = $actions;
            }

            $entitiesConfiguration[$entityName] = $entityConfiguration;
        }

        $backendConfiguration['entities'] = $entitiesConfiguration;

        return $backendConfiguration;
    }

    /**
     * Returns the default actions defined by EasyAdmin for the given view.
     * This allows to provide some nice defaults for backends that don't
     * define their own actions.
     *
     * @param  string $view
     * @return array
     */
    private function getDefaultActions($view)
    {
        // basic configuration for default actions
        $actions = $this->normalizeActionsConfiguration(array(
            array('name' => 'delete', 'label' => 'action.delete', 'type' => 'method', 'icon' => 'trash'),
            array('name' => 'edit',   'label' => 'action.edit', 'type' => 'method', 'icon' => 'edit'),
            array('name' => 'new',    'label' => 'action.new', 'type' => 'method',),
            array('name' => 'search', 'label' => 'action.search', 'type' => 'method',),
            array('name' => 'show',   'label' => 'action.show', 'type' => 'method',),
            array('name' => 'list',   'label' => 'action.list', 'type' => 'method',),
        ));

        // configure which actions are enabled for each view
        $actionsPerView = array(
            'edit'   => array('delete' => $actions['delete'], 'list' => $actions['list']),
            'list'   => array('show' => $actions['show'], 'edit' => $actions['edit'], 'search' => $actions['search'], 'new' => $actions['new']),
            'new'    => array('list' => $actions['list']),
            'show'   => array('delete' => $actions['delete'], 'list' => $actions['list'], 'edit' => $actions['edit']),
        );

        // minor tweaks for some action + view combinations
        $actionsPerView['list']['edit']['icon'] = null;

        return $actionsPerView[$view];
    }

    /**
     * Transforms the different action configuration formats into a normalized
     * and expanded format. These are the two simple formats allowed:
     *
     * # Config format #1: no custom option
     * easy_admin:
     *     entities:
     *         User:
     *             list:
     *                 actions: ['search', 'show', 'grantAccess']
     *
     * # Config format #2: one or more actions define any of its options
     * easy_admin:
     *     entities:
     *         User:
     *             list:
     *                 actions: ['search', { name: 'show', label: 'Show', 'icon': 'user' }, 'grantAccess']
     *
     * @param  array  $actionConfiguration
     * @return array
     */
    private function normalizeActionsConfiguration(array $actionConfiguration)
    {
        $configuration = array();

        foreach ($actionConfiguration as $action) {
            if (!is_string($action) && !is_array($action)) {
                throw new \RuntimeException('The values of the "actions" option can only be strings or arrays.');
            }

            // config format #1
            if (is_string($action)) {
                $action = array('name' => $action);
            }

            $normalizedConfiguration = array_replace($this->defaultActionConfiguration, $action);

            // 'name' is the only mandatory option for actions
            if (!isset($action['name'])) {
                throw new \RuntimeException('Customized entity actions must define their "name" option.');
            }

            if (!isset($action['type'])) {
                $action['type'] = 'method';
            }

            $actionName = $normalizedConfiguration['name'];

            // use the special 'action.<view name>' label for the default actions
            if (null === $normalizedConfiguration['label'] && in_array($actionName, array('delete', 'edit', 'new', 'search', 'show', 'list'))) {
                $normalizedConfiguration['label'] = 'action.'.$actionName;
            }

            // actions without a custom label use their name as label
            if (null === $normalizedConfiguration['label']) {
                // copied from Symfony\Component\Form\FormRenderer::humanize() (author: Bernhard Schussek <bschussek@gmail.com>)
                $label = ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $actionName))));
                $normalizedConfiguration['label'] = $label;
            }

            $configuration[$actionName] = $normalizedConfiguration;
        }

        return $configuration;
    }

    /**
     * Removes the actions marked as deleted from the given action configuration.
     * If the name of an action starts with a '-' dash, it must be removed.
     *
     * @param  array $actionConfiguration
     * @return array
     */
    private function filterRemovedActions(array $actionConfiguration)
    {
        // action names prefixed with '-' make those actions to be removed.
        // e.g. '-search' removes both '-search' and 'search' (if present)
        $removedActionNames = array();
        foreach ($actionConfiguration as $action) {
            if ('-' === $action['name']{0}) {
                $removedActionNames[] = $action['name'];
                $removedActionNames[] = substr($action['name'], 1);
            }
        }

        $actionConfiguration = array_filter($actionConfiguration, function($action) use ($removedActionNames) {
            return !in_array($action['name'], $removedActionNames);
        });

        // 'list' action is mandatory for all views
        if (!array_key_exists('list', $actionConfiguration)) {
            $actionConfiguration = array_merge($actionConfiguration, $this->normalizeActionsConfiguration(array('list')));
        }

        return $actionConfiguration;
    }

    /**
     * Normalizes and initializes the configuration of the given entities to
     * simplify the option processing of the other methods and functions.
     *
     * @param  array $entitiesConfiguration
     * @return array The configured entities
     */
    private function processEntitiesConfiguration(array $entitiesConfiguration)
    {
        $entities = array();

        foreach ($entitiesConfiguration as $entityName => $entityConfiguration) {
            // copy the original entity configuration to not lose any of its options
            $config = $entityConfiguration;

            // if the special 'form' view is defined, use its options to complete
            // the configuration for the 'new' and 'edit' views
            if (isset($config['form'])) {
                $config['new'] = isset($config['new']) ? array_replace($config['form'], $config['new']) : $config['form'];
                $config['edit'] = isset($config['edit']) ? array_replace($config['form'], $config['edit']) : $config['form'];
            }

            // configuration for the views related to the entity ('list', 'edit', etc.)
            foreach (array('edit', 'list', 'new', 'show') as $view) {
                // if needed, initialize options to simplify further configuration processing
                if (!isset($config[$view])) {
                    $config[$view] = array('fields' => array());
                }

                if (!isset($config[$view]['fields'])) {
                    $config[$view]['fields'] = array();
                }

                if (count($config[$view]['fields']) > 0) {
                    $config[$view]['fields'] = $this->normalizeFieldsConfiguration($config[$view]['fields'], $view, $entityConfiguration);
                }
            }

            $entities[$entityName] = $config;
        }

        return $entities;
    }

    /**
     * The name of the entity is used in the URLs of the application to define the
     * entity which should be used for each view. Obviously, the entity name
     * must be unique in the application to identify entities unequivocally.
     *
     * This method ensures that all entity names are unique by appending some suffix
     * to repeated names until they are unique.
     *
     * @param  array $entitiesConfiguration
     * @return array The entities configuration with unique entity names
     */
    private function getUniqueEntityName($entityName, $existingEntityNames)
    {
        $uniqueName = $entityName;

        while (in_array($uniqueName, $existingEntityNames)) {
            $uniqueName .= '_';
        }

        return $uniqueName;
    }

    /**
     * Views can define their fields using two different formats:
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
     * @param  array  $fieldsConfiguration
     * @param  string $view                The current view (this argument is needed to create good error messages)
     * @param  array  $entityConfiguration The full configuration of the entity this field belongs to
     * @return array  The configured entity fields
     */
    private function normalizeFieldsConfiguration(array $fieldsConfiguration, $view, array $entityConfiguration)
    {
        $fields = array();

        foreach ($fieldsConfiguration as $field) {
            if (!is_string($field) && !is_array($field)) {
                throw new \RuntimeException(sprintf('The values of the "fields" option for the "%s" view of the "%s" entity can only be strings or arrays.', $view, $entityConfiguration['class']));
            }

            if (is_string($field)) {
                // Config format #1: field is just a string representing the entity property
                $fieldConfiguration = array('property' => $field);
            } else {
                // Config format #1: field is an array that defines one or more
                // options. check that the mandatory 'property' option is set
                if (!array_key_exists('property', $field)) {
                    throw new \RuntimeException(sprintf('One of the values of the "fields" option for the "%s" view of the "%s" entity does not define the "property" option.', $view, $entityConfiguration['class']));
                }

                $fieldConfiguration = $field;
            }

            // for 'image' type fields, if the entity defines an 'image_base_path'
            // option, but the field does not, use the value defined by the entity
            if (isset($fieldConfiguration['type']) && 'image' === $fieldConfiguration['type']) {
                if (!isset($fieldConfiguration['base_path']) && isset($entityConfiguration['image_base_path'])) {
                    $fieldConfiguration['base_path'] = $entityConfiguration['image_base_path'];
                }
            }

            $fieldName = $fieldConfiguration['property'];
            $fields[$fieldName] = $fieldConfiguration;
        }

        return $fields;
    }
}
