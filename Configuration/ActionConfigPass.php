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
 * Merges all the actions that can be configured in the backend and normalizes
 * them to get the final action configuration for each entity view.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ActionConfigPass implements ConfigPassInterface
{
    private $defaultActionConfig = array(
        // either the name of a controller method or an application route (it depends on the 'type' option)
        'name' => null,
        // 'method' if the action is a controller method; 'route' if it's an application route
        'type' => 'method',
        // action label (displayed as link or button) (if 'null', autogenerate it)
        'label' => null,
        // the CSS class applied to the button/link displayed by the action
        'css_class' => '',
        // the name of the FontAwesome icon to display next to the 'label' (doesn't include the 'fa-' prefix)
        'icon' => null,
    );

    public function process(array $backendConfig)
    {
        $entitiesConfig = array();
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            // first, define the disabled actions
            $actionsDisabledByBackend = $backendConfig['disabled_actions'];
            $actionsDisabledByEntity = isset($entityConfig['disabled_actions']) ? $entityConfig['disabled_actions'] : array();
            $disabledActions = array_unique(array_merge($actionsDisabledByBackend, $actionsDisabledByEntity));
            $entityConfig['disabled_actions'] = $disabledActions;

            // second, define the actions of each entity view
            foreach (array('edit', 'list', 'new', 'show') as $view) {
                $defaultActions = $this->getDefaultActions($view);
                $backendActions = isset($backendConfig[$view]['actions']) ? $backendConfig[$view]['actions'] : array();
                $backendActions = $this->normalizeActionsConfig($backendActions, $defaultActions);

                $defaultViewActions = array_replace($defaultActions, $backendActions);
                $defaultViewActions = $this->filterRemovedActions($defaultViewActions);

                $entityActions = isset($entityConfig[$view]['actions']) ? $entityConfig[$view]['actions'] : array();
                $entityActions = $this->normalizeActionsConfig($entityActions, $defaultViewActions);

                $viewActions = array_replace($defaultViewActions, $entityActions);
                $viewActions = $this->filterRemovedActions($viewActions);

                // 'list' action is mandatory for all views
                if (!array_key_exists('list', $viewActions)) {
                    $viewActions = array_merge($viewActions, $this->normalizeActionsConfig(array('list')));
                }

                if (isset($viewActions['delete'])) {
                    if ('list' === $view) {
                        $viewActions['delete']['css_class'] .= ' text-danger';
                    }
                }

                $entityConfig[$view]['actions'] = $viewActions;
            }

            $entitiesConfig[$entityName] = $entityConfig;
        }

        $backendConfig['entities'] = $entitiesConfig;

        return $backendConfig;
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
        $actions = $this->normalizeActionsConfig(array(
            array('name' => 'delete', 'label' => 'action.delete', 'icon' => 'trash'),
            array('name' => 'edit', 'label' => 'action.edit', 'icon' => 'edit'),
            array('name' => 'new', 'label' => 'action.new'),
            array('name' => 'search', 'label' => 'action.search'),
            array('name' => 'show', 'label' => 'action.show'),
            array('name' => 'list', 'label' => 'action.list'),
        ));

        // define which actions are enabled for each view
        $actionsPerView = array(
            'edit' => array('delete' => $actions['delete'], 'list' => $actions['list']),
            'list' => array('show' => $actions['show'], 'edit' => $actions['edit'], 'search' => $actions['search'], 'new' => $actions['new']),
            'new' => array('list' => $actions['list']),
            'show' => array('delete' => $actions['delete'], 'list' => $actions['list'], 'edit' => $actions['edit']),
        );

        // minor tweaks for some action + view combinations
        $actionsPerView['list']['edit']['icon'] = null;

        return $actionsPerView[$view];
    }

    /**
     * Transforms the different action configuration formats into a normalized
     * and expanded format. These are the two simple formats allowed:.
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
     * @param array $actionsConfig
     * @param array $defaultActionsConfig
     *
     * @return array
     */
    private function normalizeActionsConfig(array $actionsConfig, array $defaultActionsConfig = array())
    {
        $configuration = array();

        foreach ($actionsConfig as $action) {
            if (is_string($action)) {
                // config format #1
                $actionConfig = array('name' => $action);
            } elseif (is_array($action)) {
                // config format #2
                $actionConfig = $action;
            } else {
                throw new \RuntimeException('The values of the "actions" option can only be strings or arrays.');
            }

            // 'name' is the only mandatory option for actions
            if (!isset($actionConfig['name'])) {
                throw new \RuntimeException('When using the expanded configuration format for actions, you must define their "name" option.');
            }

            $actionName = $actionConfig['name'];

            // 'name' value is used as the class method name or the Symfony route name
            // check that its value complies with the PHP method name rules (the leading dash
            // is exceptionally allowed to support the configuration format of removed actions)
            if (!$this->isValidMethodName('-' === $actionName[0] ? substr($actionName, 1) : $actionName)) {
                throw new \InvalidArgumentException(sprintf('The name of the "%s" action contains invalid characters (allowed: letters, numbers, underscores; the first character cannot be a number).', $actionName));
            }

            $normalizedConfig = array_replace($this->defaultActionConfig, $actionConfig);

            $actionName = $normalizedConfig['name'];

            // use the special 'action.<action name>' label for the default actions
            // only if the user hasn't defined a custom label (which can also be an empty string)
            if (null === $normalizedConfig['label'] && in_array($actionName, array('delete', 'edit', 'new', 'search', 'show', 'list'))) {
                $normalizedConfig['label'] = 'action.'.$actionName;
            }

            // the rest of actions without a custom label use their name as label
            if (null === $normalizedConfig['label']) {
                // copied from Symfony\Component\Form\FormRenderer::humanize() (author: Bernhard Schussek <bschussek@gmail.com>)
                $label = ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $actionName))));
                $normalizedConfig['label'] = $label;
            }

            if (count($defaultActionsConfig)) {
                // if the user defines an action with the same name of a default action,
                // he/she is in fact overriding the default configuration of that action.
                // for example: actions: ['delete', 'list']
                // this condition ensures that when the user doesn't define the value for
                // some option of the action (for example the icon or the label) that
                // option is actually added with the right default value. Otherwise,
                // those options would be 'null' and the template would show some issues
                if (array_key_exists($actionName, $defaultActionsConfig)) {
                    // remove null config options but maintain empty options (this allows to set an empty label for the action)
                    $normalizedConfig = array_filter($normalizedConfig, function ($element) { return null !== $element; });
                    $normalizedConfig = array_replace($defaultActionsConfig[$actionName], $normalizedConfig);
                }
            }

            // Add default classes ("action-{actionName}") to each action configuration
            $normalizedConfig['css_class'] .= ' action-'.$normalizedConfig['name'];
            $normalizedConfig['css_class'] = ltrim($normalizedConfig['css_class']);

            $configuration[$actionName] = $normalizedConfig;
        }

        return $configuration;
    }

    /**
     * Removes the actions marked as deleted from the given actions configuration.
     *
     * @param array $actionsConfig
     *
     * @return array
     */
    private function filterRemovedActions(array $actionsConfig)
    {
        // if the name of the action starts with a dash ('-'), remove it
        $removedActions = array_filter($actionsConfig, function ($action) {
            return '-' === $action['name']{0};
        });

        if (empty($removedActions)) {
            return $actionsConfig;
        }

        return array_filter($actionsConfig, function ($action) use ($removedActions) {
            // e.g. '-search' action name removes both '-search' and 'search' (if exists)
            return !array_key_exists($action['name'], $removedActions)
                && !array_key_exists('-'.$action['name'], $removedActions);
        });
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
}
