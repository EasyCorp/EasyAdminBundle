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
    private $views = array('edit', 'list', 'new', 'show');
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
        $backendConfig = $this->processDisabledActions($backendConfig);
        $backendConfig = $this->normalizeActionsConfig($backendConfig);
        $backendConfig = $this->resolveActionInheritance($backendConfig);
        $backendConfig = $this->filterRemovedActions($backendConfig);
        $backendConfig = $this->processActionsConfig($backendConfig);

        return $backendConfig;
    }

    private function processDisabledActions(array $backendConfig)
    {
        $actionsDisabledByBackend = $backendConfig['disabled_actions'];
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $actionsDisabledByEntity = isset($entityConfig['disabled_actions']) ? $entityConfig['disabled_actions'] : array();
            $disabledActions = array_unique(array_merge($actionsDisabledByBackend, $actionsDisabledByEntity));

            $backendConfig['entities'][$entityName]['disabled_actions'] = $disabledActions;
        }

        return $backendConfig;
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
    private function normalizeActionsConfig(array $backendConfig)
    {
        // first, normalize actions defined globally for the entire backend
        foreach ($this->views as $view) {
            $actionsConfig = $backendConfig[$view]['actions'];
            $backendConfig[$view]['actions'] = $this->doNormalizeActionsConfig($actionsConfig, sprintf('the global "%s" view defined under "easy_admin" option', $view));
        }

        // second, normalize actions defined for each entity
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($this->views as $view) {
                $actionsConfig = $entityConfig[$view]['actions'];
                $backendConfig['entities'][$entityName][$view]['actions'] = $this->doNormalizeActionsConfig($actionsConfig, sprintf('the "%s" view of the "%s" entity', $view, $entityName));
            }
        }

        return $backendConfig;
    }

    private function doNormalizeActionsConfig(array $actionsConfig, $errorOrigin = '')
    {
        $normalizedConfig = array();

        foreach ($actionsConfig as $i => $actionConfig) {
            if (!is_string($actionConfig) && !is_array($actionConfig)) {
                throw new \RuntimeException(sprintf('One of the actions defined by %s contains an invalid value (action config can only be a YAML string or hash).', $errorOrigin));
            }

            // config format #1
            if (is_string($actionConfig)) {
                $actionConfig = array('name' => $actionConfig);
            }

            $actionConfig = array_merge($this->defaultActionConfig, $actionConfig);

            // 'name' is the only mandatory option for actions (it might
            // be missing when using the config format #2)
            if (!isset($actionConfig['name'])) {
                throw new \RuntimeException(sprintf('One of the actions defined by %s does not define its name, which is the only mandatory option for actions.', $errorOrigin));
            }

            $actionName = $actionConfig['name'];
            $normalizedConfig[$actionName] = $actionConfig;
        }

        return $normalizedConfig;
    }

    private function resolveActionInheritance(array $backendConfig)
    {
        $defaultActions = $this->getDefaultActions();

        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($this->views as $view) {
                $actionsConfig = array_merge(
                    $defaultActions[$view],
                    $backendConfig[$view]['actions'],
                    $entityConfig[$view]['actions']
                );

                $backendConfig['entities'][$entityName][$view]['actions'] = $actionsConfig;
            }
        }

        return $backendConfig;
    }

    /**
     * Removes the actions marked as deleted from the given actions configuration.
     *
     * @param array $actionsConfig
     *
     * @return array
     */
    private function filterRemovedActions(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($this->views as $view) {
                $actionsConfig = $entityConfig[$view]['actions'];
                // if the name of the action starts with a dash ('-'), remove it
                $removedActions = array_filter($actionsConfig, function ($action) {
                    return '-' === $action['name']{0};
                });

                $filteredActions = array_filter($actionsConfig, function ($action) use ($removedActions) {
                    // e.g. '-search' action name removes both '-search' and 'search' (if exists)
                    return !array_key_exists($action['name'], $removedActions)
                        && !array_key_exists('-'.$action['name'], $removedActions);
                });

                $backendConfig['entities'][$entityName][$view]['actions'] = $filteredActions;
            }
        }

        return $backendConfig;
    }

    private function processActionsConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($this->views as $view) {
                foreach ($entityConfig[$view]['actions'] as $actionName => $actionConfig) {
                    // 'name' value is used as the class method name or the Symfony route name
                    // check that its value complies with the PHP method name rules
                    if (!$this->isValidMethodName($actionName)) {
                        throw new \InvalidArgumentException(sprintf('The name of the "%s" action defined in the "%s" view of the "%s" entity contains invalid characters (allowed: letters, numbers, underscores; the first character cannot be a number).', $actionName, $view, $entityName));
                    }

                    if (null === $actionConfig['label']) {
                        $actionConfig['label'] = $this->humanizeString($actionName);
                    }

                    // Add default classes ("action-{actionName}") to each action configuration
                    $actionConfig['css_class'] .= ' action-'.$actionName;

                    $backendConfig['entities'][$entityName][$view]['actions'][$actionName] = $actionConfig;
                }
            }
        }

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
    private function getDefaultActions()
    {
        // basic configuration for default actions
        $actions = $this->doNormalizeActionsConfig(array(
            'delete' => array('name' => 'delete', 'label' => 'action.delete', 'icon' => 'trash-o', 'css_class' => 'btn btn-default'),
            'edit' => array('name' => 'edit', 'label' => 'action.edit', 'icon' => 'edit', 'css_class' => 'btn btn-primary'),
            'new' => array('name' => 'new', 'label' => 'action.new'),
            'search' => array('name' => 'search', 'label' => 'action.search'),
            'show' => array('name' => 'show', 'label' => 'action.show'),
            'list' => array('name' => 'list', 'label' => 'action.list', 'css_class' => 'btn btn-secondary'),
        ));

        // define which actions are enabled for each view
        $actionsPerView = array(
            'edit' => array('delete' => $actions['delete'], 'list' => $actions['list']),
            'list' => array('edit' => $actions['edit'], 'delete' => $actions['delete'], 'search' => $actions['search'], 'new' => $actions['new']),
            'new' => array('list' => $actions['list']),
            'show' => array('edit' => $actions['edit'], 'delete' => $actions['delete'], 'list' => $actions['list']),
        );

        // minor tweaks for some action + view combinations
        $actionsPerView['list']['delete']['icon'] = null;
        $actionsPerView['list']['delete']['css_class'] = 'text-danger';
        $actionsPerView['list']['edit']['icon'] = null;
        $actionsPerView['list']['edit']['css_class'] = 'text-primary';

        return $actionsPerView;
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
        return 0 !== preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name);
    }

    /**
     * copied from Symfony\Component\Form\FormRenderer::humanize()
     * (author: Bernhard Schussek <bschussek@gmail.com>)
     *
     * @param string $content
     *
     * @return string
     */
    private function humanizeString($content)
    {
        return ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $content))));
    }
}
