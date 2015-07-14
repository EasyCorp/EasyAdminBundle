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
        'name'  => null,     // either the name of a controller method or an application route (it depends on the 'type' option)
        'type'  => 'method', // 'method' if the action is a controller method; 'route' if it's an application route
        'label' => null,     // action label (displayed as link or button) (if 'null', autogenerate it)
        'class' => '',       // the CSS class applied to the button/link displayed by the action
        'icon'  => null,     // the name of the FontAwesome icon to display next to the 'label' (doesn't include the 'fa-' prefix)
    );

    private $defaultBackendTemplates = array(
        'layout' => '@EasyAdmin/default/layout.html.twig',
        'edit' => '@EasyAdmin/default/edit.html.twig',
        'list' => '@EasyAdmin/default/list.html.twig',
        'new' => '@EasyAdmin/default/new.html.twig',
        'show' => '@EasyAdmin/default/show.html.twig',
        'form' => '@EasyAdmin/default/form.html.twig',
        'paginator' => '@EasyAdmin/default/paginator.html.twig',
        'field_array' => '@EasyAdmin/default/field_array.html.twig',
        'field_association' => '@EasyAdmin/default/field_association.html.twig',
        'field_bigint' => '@EasyAdmin/default/field_bigint.html.twig',
        'field_boolean' => '@EasyAdmin/default/field_boolean.html.twig',
        'field_date' => '@EasyAdmin/default/field_date.html.twig',
        'field_datetime' => '@EasyAdmin/default/field_datetime.html.twig',
        'field_datetimetz' => '@EasyAdmin/default/field_datetimetz.html.twig',
        'field_decimal' => '@EasyAdmin/default/field_decimal.html.twig',
        'field_float' => '@EasyAdmin/default/field_float.html.twig',
        'field_id' => '@EasyAdmin/default/field_id.html.twig',
        'field_image' => '@EasyAdmin/default/field_image.html.twig',
        'field_integer' => '@EasyAdmin/default/field_integer.html.twig',
        'field_simple_array' => '@EasyAdmin/default/field_simple_array.html.twig',
        'field_smallint' => '@EasyAdmin/default/field_smallint.html.twig',
        'field_string' => '@EasyAdmin/default/field_string.html.twig',
        'field_text' => '@EasyAdmin/default/field_text.html.twig',
        'field_time' => '@EasyAdmin/default/field_time.html.twig',
        'field_toggle' => '@EasyAdmin/default/field_toggle.html.twig',
        'label_empty' => '@EasyAdmin/default/label_empty.html.twig',
        'label_inaccessible' => '@EasyAdmin/default/label_inaccessible.html.twig',
        'label_null' => '@EasyAdmin/default/label_null.html.twig',
        'label_undefined' => '@EasyAdmin/default/label_undefined.html.twig',
    );

    private $kernelRootDir;

    public function load(array $configs, ContainerBuilder $container)
    {
        $this->kernelRootDir = $container->getParameter('kernel.root_dir');

        // process bundle's configuration parameters
        $backendConfiguration = $this->processConfiguration(new Configuration(), $configs);
        $backendConfiguration['entities'] = $this->getEntitiesConfiguration($backendConfiguration['entities']);
        $backendConfiguration = $this->processEntityActions($backendConfiguration);
        $backendConfiguration = $this->processEntityTemplates($backendConfiguration);

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
     * @param array $entitiesConfiguration
     *
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
     * @param array $entitiesConfiguration The entity configuration in one of the simplified formats
     *
     * @return array The normalized configuration
     */
    private function normalizeEntitiesConfiguration(array $entitiesConfiguration)
    {
        $normalizedConfiguration = array();

        foreach ($entitiesConfiguration as $entityName => $entityConfiguration) {
            // normalize config formats #1 and #2 to use the 'class' option as config format #3
            if (!is_array($entityConfiguration)) {
                $entityConfiguration = array('class' => $entityConfiguration);
            }

            // if config format #3 is used, ensure that it defines the 'class' option
            if (!isset($entityConfiguration['class'])) {
                throw new \RuntimeException(sprintf('The "%s" entity must define its associated Doctrine entity class using the "class" option.', $entityName));
            }

            // if config format #1 is used, the entity name is the numeric index
            // of the configuration array. In this case, autogenerate the entity
            // name using its class name
            if (is_numeric($entityName)) {
                $entityClassParts = explode('\\', $entityConfiguration['class']);
                $entityClassName = end($entityClassParts);
                $entityName = $this->getUniqueEntityName($entityClassName, array_keys($normalizedConfiguration));
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
            if (!isset($entityConfiguration['label'])) {
                $entityConfiguration['label'] = $entityName;
            }

            $entityConfiguration['name'] = $entityName;
            $normalizedConfiguration[$entityName] = $entityConfiguration;
        }

        return $normalizedConfiguration;
    }

    /**
     * Merges all the actions that can be configured in the backend and normalizes
     * them to get the final action configuration for each entity view.
     *
     * @param array $backendConfiguration
     *
     * @return array
     */
    public function processEntityActions(array $backendConfiguration)
    {
        $entitiesConfiguration = array();

        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            // first, define the disabled actions
            $actionsDisabledByBackend = $backendConfiguration['disabled_actions'];
            $actionsDisabledByEntity = isset($entityConfiguration['disabled_actions']) ? $entityConfiguration['disabled_actions'] : array();
            $disabledActions = array_unique(array_merge($actionsDisabledByBackend, $actionsDisabledByEntity));
            $entityConfiguration['disabled_actions'] = $disabledActions;

            // second, define the actions of each entity view
            foreach (array('edit', 'list', 'new', 'show') as $view) {
                $defaultActions = $this->getDefaultActions($view);
                $backendActions = isset($backendConfiguration[$view]['actions']) ? $backendConfiguration[$view]['actions'] : array();
                $backendActions = $this->normalizeActionsConfiguration($backendActions, $defaultActions);

                $defaultViewActions = array_replace($defaultActions, $backendActions);
                $defaultViewActions = $this->filterRemovedActions($defaultViewActions);

                $entityActions = isset($entityConfiguration[$view]['actions']) ? $entityConfiguration[$view]['actions'] : array();
                $entityActions = $this->normalizeActionsConfiguration($entityActions, $defaultViewActions);

                $viewActions = array_replace($defaultViewActions, $entityActions);
                $viewActions = $this->filterRemovedActions($viewActions);

                // 'list' action is mandatory for all views
                if (!array_key_exists('list', $viewActions)) {
                    $viewActions = array_merge($viewActions, $this->normalizeActionsConfiguration(array('list')));
                }

                $entityConfiguration[$view]['actions'] = $viewActions;
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
     * @param string $view
     *
     * @return array
     */
    private function getDefaultActions($view)
    {
        // basic configuration for default actions
        $actions = $this->normalizeActionsConfiguration(array(
            array('name' => 'delete', 'label' => 'action.delete', 'icon' => 'trash'),
            array('name' => 'edit',   'label' => 'action.edit',   'icon' => 'edit'),
            array('name' => 'new',    'label' => 'action.new'),
            array('name' => 'search', 'label' => 'action.search'),
            array('name' => 'show',   'label' => 'action.show'),
            array('name' => 'list',   'label' => 'action.list'),
        ));

        // define which actions are enabled for each view
        $actionsPerView = array(
            'edit' => array('delete' => $actions['delete'], 'list' => $actions['list']),
            'list' => array('show' => $actions['show'], 'edit' => $actions['edit'], 'search' => $actions['search'], 'new' => $actions['new']),
            'new'  => array('list' => $actions['list']),
            'show' => array('delete' => $actions['delete'], 'list' => $actions['list'], 'edit' => $actions['edit']),
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
     * # Config format #2: one or more actions define any of their options
     * easy_admin:
     *     entities:
     *         User:
     *             list:
     *                 actions: ['search', { name: 'show', label: 'Show', 'icon': 'user' }, 'grantAccess']
     *
     * @param array $actionsConfiguration
     * @param array $defaultActionsConfiguration
     *
     * @return array
     */
    private function normalizeActionsConfiguration(array $actionsConfiguration, array $defaultActionsConfiguration = array())
    {
        $configuration = array();

        foreach ($actionsConfiguration as $action) {
            if (is_string($action)) {
                // config format #1
                $actionConfiguration = array('name' => $action);
            } elseif (is_array($action)) {
                // config format #2
                $actionConfiguration = $action;
            } else {
                throw new \RuntimeException('The values of the "actions" option can only be strings or arrays.');
            }

            // 'name' is the only mandatory option for actions
            if (!isset($actionConfiguration['name'])) {
                throw new \RuntimeException('When using the expanded configuration format for actions, you must define their "name" option.');
            }

            $actionName = $actionConfiguration['name'];

            // 'name' value is used as the class method name or the Symfony route name
            // check that its value complies with the PHP method name rules (the leading dash
            // is exceptionally allowed to support the configuration format of removed actions)
            if (!$this->isValidMethodName('-' === $actionName[0] ? substr($actionName, 1) : $actionName)) {
                throw new \InvalidArgumentException(sprintf('The name of the "%s" action contains invalid characters (allowed: letters, numbers, underscores; the first character cannot be a number).', $actionName));
            }

            $normalizedConfiguration = array_replace($this->defaultActionConfiguration, $actionConfiguration);

            $actionName = $normalizedConfiguration['name'];

            // use the special 'action.<action name>' label for the default actions
            // only if the user hasn't defined a custom label (which can also be an empty string)
            if (null === $normalizedConfiguration['label'] && in_array($actionName, array('delete', 'edit', 'new', 'search', 'show', 'list'))) {
                $normalizedConfiguration['label'] = 'action.'.$actionName;
            }

            // the rest of actions without a custom label use their name as label
            if (null === $normalizedConfiguration['label']) {
                // copied from Symfony\Component\Form\FormRenderer::humanize() (author: Bernhard Schussek <bschussek@gmail.com>)
                $label = ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $actionName))));
                $normalizedConfiguration['label'] = $label;
            }

            if (count($defaultActionsConfiguration)) {
                // if the user defines an action with the same name of a default action,
                // he/she is in fact overriding the default configuration of that action.
                // for example: actions: ['delete', 'list']
                // this condition ensures that when the user doesn't define the value for
                // some option of the action (for example the icon or the label) that
                // option is actually added with the right default value. Otherwise,
                // those options would be 'null' and the template would show some issues
                if (array_key_exists($actionName, $defaultActionsConfiguration)) {
                    // remove null config options but maintain empty options (this allows to set an empty label for the action)
                    $normalizedConfiguration = array_filter($normalizedConfiguration, function($element) { return null !== $element; });
                    $normalizedConfiguration = array_replace($defaultActionsConfiguration[$actionName], $normalizedConfiguration);
                }
            }

            $configuration[$actionName] = $normalizedConfiguration;
        }

        return $configuration;
    }

    /**
     * Removes the actions marked as deleted from the given actions configuration.
     *
     * @param array $actionsConfiguration
     *
     * @return array
     */
    private function filterRemovedActions(array $actionsConfiguration)
    {
        // if the name of the action starts with a '-' dash, remove it
        $removedActions = array_filter($actionsConfiguration, function ($action) {
            return '-' === $action['name']{0};
        });

        if (empty($removedActions)) {
            return $actionsConfiguration;
        }

        return array_filter($actionsConfiguration, function ($action) use ($removedActions) {
            // e.g. '-search' action name removes both '-search' and 'search' (if exists)
            return !array_key_exists($action['name'], $removedActions)
                && !array_key_exists('-'.$action['name'], $removedActions);
        });
    }

    /**
     * Determines the template used to render each backend element. This is not
     * trivial because templates can depend on the entity displayed and they
     * define an advanced override mechanism.
     *
     * @param array $backendConfiguration
     *
     * @return array
     */
    private function processEntityTemplates(array $backendConfiguration)
    {
        $applicationTemplatesDir = $this->kernelRootDir.'/Resources/views';
        $bundleTemplatesDir =  $this->kernelRootDir.'/../vendor/javiereguiluz/easyadmin-bundle/Resources/views';

        $customFieldTypesTemplates = $this->getCustomFieldTypesTemplates($backendConfiguration);
        $templates = array_merge($this->defaultBackendTemplates, $customFieldTypesTemplates);

        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach ($templates as $templateName => $defaultTemplatePath) {
                // 1st level priority: easy_admin.entities.<entityName>.templates.<templateName> config option
                if (isset($entityConfiguration['templates'][$templateName])) {
                    $template = $entityConfiguration['templates'][$templateName];
                // 2nd level priority: easy_admin.design.templates.<templateName> config option
                } elseif (isset($backendConfiguration['design']['templates'][$templateName])) {
                    $template = $backendConfiguration['design']['templates'][$templateName];
                // 3rd level priority: app/Resources/views/easy_admin/<entityName>/<templateName>.html.twig
                } elseif (file_exists($applicationTemplatesDir.'/easy_admin/'.$entityName.'/'.$templateName.'.html.twig')) {
                    $template = 'easy_admin/'.$entityName.'/'.$templateName.'.html.twig';
                // 4th level priority: app/Resources/views/easy_admin/<templateName>.html.twig
                } elseif (file_exists($applicationTemplatesDir.'/easy_admin/'.$templateName.'.html.twig')) {
                    $template = 'easy_admin/'.$templateName.'.html.twig';
                // 5th level priority: @EasyAdmin/default/<templateName>.html.twig
                } else {
                    if (array_key_exists($templateName, $customFieldTypesTemplates)) {
                        throw new \RuntimeException(sprintf('The "%s" entity uses a custom data type called "%s" but its associated template is not defined in "app/resources/views/easy_admin/"', $entityName, str_replace('field_', '', $templateName)));
                    }

                    $template = $defaultTemplatePath;
                }

                $entityConfiguration['templates'][$templateName] = $template;
            }

            $backendConfiguration['entities'][$entityName] = $entityConfiguration;
        }

        return $backendConfiguration;
    }

    /**
     * Returns the template names and paths for the custom field types defined
     * on-the-fly by the entity for the 'show' and 'list' actions.
     *
     * @param array $backendConfiguration
     *
     * @return array
     */
    private function getCustomFieldTypesTemplates(array $backendConfiguration)
    {
        $customTemplates = array();

        // this 'array_flip()' nonsense is needed to apply 'array_filter()' on the keys instead of the values
        $defaultFieldTypesTemplates = array_flip(array_filter(array_flip($this->defaultBackendTemplates), function ($key) {
            return 'field_' === substr($key, 0, 6);
        }));

        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            foreach (array('show', 'list') as $action) {
                foreach ($entityConfiguration[$action]['fields'] as $fieldName => $fieldConfiguration) {
                    if (isset($fieldConfiguration['type']) && !array_key_exists($fieldTemplateName = 'field_'.$fieldConfiguration['type'], $defaultFieldTypesTemplates)) {
                        $customTemplates[$fieldTemplateName] = '@EasyAdmin/default/'.$fieldTemplateName.'.html.twig';
                    }
                }
            }
        }

        return array_unique($customTemplates);
    }

    /**
     * Normalizes and initializes the configuration of the given entities to
     * simplify the option processing of the other methods and functions.
     *
     * @param array $entitiesConfiguration
     *
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
            foreach (array('edit', 'list', 'new', 'search', 'show') as $view) {
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
     * @param array  $fieldsConfiguration
     * @param string $view                The current view (this argument is needed to create good error messages)
     * @param array  $entityConfiguration The full configuration of the entity this field belongs to
     *
     * @return array The configured entity fields
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

    /**
     * Checks whether the given string is valid as a PHP method name.
     *
     * @param  string  $name
     * @return boolean
     */
    private function isValidMethodName($name)
    {
        return preg_match('/^-?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches);
    }
}
