<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

/**
 * Merges all the actions that can be configured in the backend and normalizes
 * them to get the final action configuration for each entity view.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ActionConfigPass implements ConfigPassInterface
{
    private $views = ['edit', 'list', 'new', 'show'];
    private $defaultActionConfig = [
        // either the name of a controller method or an application route (it depends on the 'type' option)
        'name' => null,
        // 'method' if the action is a controller method; 'route' if it's an application route
        'type' => 'method',
        // action label (displayed as link or button) (if 'null', autogenerate it)
        'label' => null,
        // the HTML title attribute of the action link (useful when action only displays its icon)
        'title' => null,
        // the CSS class applied to the button/link displayed by the action
        'css_class' => null,
        // the name of the FontAwesome icon to display next to the 'label' (doesn't include the 'fa-' prefix)
        'icon' => null,
        // the value of the HTML 'target' attribute add to the links of the actions (e.g. '_blank')
        'target' => '_self',
        // the value of the template. Will be set in the TemplateConfigPass, if value will stay null.
        'template' => null,
        // if TRUE, a confirmation message is displayed before executing the batch action
        'ask_confirm' => null,
    ];

    public function process(array $backendConfig)
    {
        $backendConfig = $this->processDisabledActions($backendConfig);
        $backendConfig = $this->normalizeActionsConfig($backendConfig);
        $backendConfig = $this->normalizeBatchActionsConfig($backendConfig);
        $backendConfig = $this->resolveActionInheritance($backendConfig);
        $backendConfig = $this->resolveBatchActionInheritance($backendConfig);
        $backendConfig = $this->processActionsConfig($backendConfig);
        $backendConfig = $this->processBatchActionsConfig($backendConfig);

        return $backendConfig;
    }

    private function processDisabledActions(array $backendConfig): array
    {
        $actionsDisabledByBackend = $backendConfig['disabled_actions'];
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $actionsDisabledByEntity = $entityConfig['disabled_actions'] ?? [];
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
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizeActionsConfig(array $backendConfig)
    {
        // first, normalize actions defined globally for the entire backend
        foreach ($this->views as $view) {
            $actionsConfig = $backendConfig[$view]['actions'];
            $actionsConfig = $this->doNormalizeActionsConfig($actionsConfig, sprintf('the global "%s" view defined under "easy_admin" option', $view));
            $actionsConfig = $this->doNormalizeDefaultActionsConfig($actionsConfig, $view);

            $backendConfig[$view]['actions'] = $actionsConfig;
        }

        // second, normalize actions defined for each entity
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($this->views as $view) {
                $actionsConfig = $entityConfig[$view]['actions'];
                $actionsConfig = $this->doNormalizeActionsConfig($actionsConfig, sprintf('the "%s" view of the "%s" entity', $view, $entityName));
                $actionsConfig = $this->doNormalizeDefaultActionsConfig($actionsConfig, $view);

                $backendConfig['entities'][$entityName][$view]['actions'] = $actionsConfig;
            }

            if (!\array_key_exists('collapse_actions', $entityConfig['list'])) {
                $backendConfig['entities'][$entityName]['list']['collapse_actions'] = $backendConfig['list']['collapse_actions'];
            }
        }

        return $backendConfig;
    }

    /**
     * Transforms the different batch action configuration formats into a normalized
     * and expanded format. These are the two simple formats allowed:.
     *
     * # Config format #1: no custom option
     * easy_admin:
     *     entities:
     *         User:
     *             list:
     *                 batch_actions: ['delete', 'reset']
     *
     * # Config format #2: one or more actions define any of their options
     * easy_admin:
     *     entities:
     *         User:
     *             list:
     *                 batch_actions: ['delete', { name: 'reset', label: 'Reset Account' }]
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function normalizeBatchActionsConfig(array $backendConfig)
    {
        // first, normalize batch actions defined globally for the entire backend
        $batchActionsConfig = $backendConfig['list']['batch_actions'];
        $batchActionsConfig = $this->doNormalizeActionsConfig($batchActionsConfig, 'the global "list" view defined under "easy_admin" option');

        $backendConfig['list']['batch_actions'] = $batchActionsConfig;
        foreach ($backendConfig['list']['batch_actions'] as $actionName => $actionConfig) {
            if ('delete' === $actionName) {
                if (null === $actionConfig['css_class']) {
                    $backendConfig['list']['batch_actions'][$actionName]['css_class'] = 'btn-danger';
                }

                if (null === $actionConfig['ask_confirm']) {
                    $backendConfig['list']['batch_actions'][$actionName]['ask_confirm'] = true;
                }
            }
        }

        // second, normalize batch actions defined for each entity
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $batchActionsConfig = $entityConfig['list']['batch_actions'];
            $batchActionsConfig = $this->doNormalizeActionsConfig($batchActionsConfig, sprintf('the "list" view of the "%s" entity', $entityName));

            foreach ($batchActionsConfig as $actionName => $actionConfig) {
                if ('delete' === $actionName) {
                    if (null === $actionConfig['css_class']) {
                        $batchActionsConfig[$actionName]['css_class'] = 'btn-danger';
                    }

                    if (null === $actionConfig['ask_confirm']) {
                        $batchActionsConfig[$actionName]['ask_confirm'] = true;
                    }
                }
            }

            $backendConfig['entities'][$entityName]['list']['batch_actions'] = $batchActionsConfig;
        }

        return $backendConfig;
    }

    private function doNormalizeActionsConfig(array $actionsConfig, $errorOrigin = '')
    {
        $normalizedConfig = [];
        $builtInActions = ['delete', 'edit', 'new', 'search', 'show', 'list'];

        foreach ($actionsConfig as $i => $actionConfig) {
            if (!\is_string($actionConfig) && !\is_array($actionConfig)) {
                throw new \RuntimeException(sprintf('One of the actions defined by %s contains an invalid value (action config can only be a YAML string or hash).', $errorOrigin));
            }

            // config format #1
            if (\is_string($actionConfig)) {
                $actionConfig = ['name' => $actionConfig];
            }

            $actionConfig = array_merge($this->defaultActionConfig, $actionConfig);

            // 'name' is the only mandatory option for actions (it might
            // be missing when using the config format #2)
            if (!isset($actionConfig['name'])) {
                throw new \RuntimeException(sprintf('One of the actions defined by %s does not define its name, which is the only mandatory option for actions.', $errorOrigin));
            }

            $actionName = $actionConfig['name'];

            if (null === $actionConfig['label']) {
                if (\in_array($actionName, $builtInActions, true)) {
                    $actionConfig['label'] = 'action.'.$actionName;
                } else {
                    $actionConfig['label'] = $this->humanizeString($actionName);
                }
            }

            $normalizedConfig[$actionName] = $actionConfig;
        }

        return $normalizedConfig;
    }

    /**
     * If the user overrides the configuration of a default action, they usually
     * define just the options they want to change. For example:
     *   actions: ['delete', 'list'] just to redefine the order
     *   actions: [ { name: 'list', label: 'Listing' }] just to redefine the label.
     *
     * For that reason, this method merges the full configuration of the default
     * actions with the new action configuration. This means that you get the
     * default value for any option that you don't explicitly set (e.g. the icon
     * or the CSS class).
     *
     * @param array  $actionsConfig
     * @param string $view
     *
     * @return array
     */
    private function doNormalizeDefaultActionsConfig(array $actionsConfig, $view)
    {
        $defaultActionsConfig = $this->getDefaultActionsConfig($view);

        foreach ($actionsConfig as $actionName => $actionConfig) {
            if (\array_key_exists($actionName, $defaultActionsConfig)) {
                // remove null config options but maintain empty options (this allows to set an empty label for the action)
                $actionConfig = array_filter($actionConfig, function ($element) {
                    return null !== $element;
                });
                $actionsConfig[$actionName] = array_merge($defaultActionsConfig[$actionName], $actionConfig);
            }
        }

        return $actionsConfig;
    }

    /**
     * Actions can be added/removed globally in the edit/list/new/show views of
     * the backend and locally in each of the configured entities. Local config always
     * wins over the global config (e.g. if backend removes 'delete' action in the
     * 'list' view but some action explicitly adds 'delete' in its 'list' view,
     * then that entity shows the 'delete' action and the others don't).
     */
    private function resolveActionInheritance(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($this->views as $view) {
                $defaultActions = $this->getDefaultActions($view);
                $backendActions = $backendConfig[$view]['actions'];
                $entityActions = $entityConfig[$view]['actions'];

                // filter actions removed in the global view configuration
                foreach ($backendActions as $backendAction) {
                    if ('-' === $backendAction['name'][0]) {
                        $actionName = mb_substr($backendAction['name'], 1);

                        unset($backendActions[$actionName], $backendActions['-'.$actionName]);

                        // unless the entity explicitly adds this globally removed action, remove it from the
                        // default actions config to avoid adding it to the entity later when merging everything
                        if (!isset($entityActions[$actionName])) {
                            unset($defaultActions[$actionName]);
                        }
                    }
                }

                // filter actions removed in the local entity configuration
                foreach ($entityActions as $entityAction) {
                    if ('-' === $entityAction['name'][0]) {
                        $actionName = mb_substr($entityAction['name'], 1);

                        unset($entityActions[$actionName], $entityActions['-'.$actionName], $defaultActions[$actionName], $backendActions[$actionName]);
                    }
                }

                $actionsConfig = array_merge($defaultActions, $backendActions, $entityActions);

                // reorder the actions to match the order set by the user in the
                // entity or in the global backend options
                if (!empty($entityActions)) {
                    $actionsConfig = $this->reorderArrayItems($actionsConfig, array_keys($entityActions));
                } elseif (!empty($backendActions)) {
                    $actionsConfig = $this->reorderArrayItems($actionsConfig, array_keys($backendActions));
                }

                $backendConfig['entities'][$entityName][$view]['actions'] = $actionsConfig;
            }
        }

        return $backendConfig;
    }

    /**
     * Batch actions can be added/removed globally in the list view of
     * the backend and locally in each of the configured entities. Local config always
     * wins over the global config (e.g. if backend removes 'delete' action in the
     * 'list' view but some action explicitly adds 'delete' in its 'list' view,
     * then that entity shows the 'delete' action and the others don't).
     */
    private function resolveBatchActionInheritance(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $backendBatchActions = $backendConfig['list']['batch_actions'];
            $entityBatchActions = $entityConfig['list']['batch_actions'];

            // filter batch actions removed in the global view configuration
            foreach ($backendBatchActions as $backendAction) {
                if ('-' === $backendAction['name'][0]) {
                    $actionName = mb_substr($backendAction['name'], 1);

                    unset($backendBatchActions[$actionName], $backendBatchActions['-'.$actionName]);
                }
            }

            // filter batch actions removed in the local entity configuration
            foreach ($entityBatchActions as $entityAction) {
                if ('-' === $entityAction['name'][0]) {
                    $actionName = mb_substr($entityAction['name'], 1);

                    unset($entityBatchActions[$actionName], $entityBatchActions['-'.$actionName], $backendBatchActions[$actionName]);
                }
            }

            $batchActionsConfig = array_merge($backendBatchActions, $entityBatchActions);

            // reorder the batch actions to match the order set by the user in the
            // entity or in the global backend options
            if (!empty($entityBatchActions)) {
                $batchActionsConfig = $this->reorderArrayItems($batchActionsConfig, array_keys($entityBatchActions));
            } elseif (!empty($backendBatchActions)) {
                $batchActionsConfig = $this->reorderArrayItems($batchActionsConfig, array_keys($backendBatchActions));
            }

            $backendConfig['entities'][$entityName]['list']['batch_actions'] = $batchActionsConfig;
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

                    // Add default classes ("action-{actionName}") to each action configuration
                    $actionConfig['css_class'] .= ' action-'.$actionName;

                    $backendConfig['entities'][$entityName][$view]['actions'][$actionName] = $actionConfig;
                }
            }
        }

        return $backendConfig;
    }

    private function processBatchActionsConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach ($entityConfig['list']['batch_actions'] as $actionName => $actionConfig) {
                if ('method' !== $actionConfig['type']) {
                    throw new \InvalidArgumentException(sprintf('Batch actions only support "method" type, "%s" given.', $actionConfig['type']));
                }

                // 'name' value is used as the class method name or the Symfony route name
                // check that its value complies with the PHP method name rules
                if (!$this->isValidMethodName($actionName)) {
                    throw new \InvalidArgumentException(sprintf('The name of the "%s" action defined in the "list" view of the "%s" entity contains invalid characters (allowed: letters, numbers, underscores; the first character cannot be a number).', $actionName, $entityName));
                }

                if (null !== $actionConfig['ask_confirm'] && !\is_bool($actionConfig['ask_confirm'])) {
                    throw new \InvalidArgumentException(sprintf('The value of the "ask_confirm" option in the "%s" batch action of the "%s" entity can only be null or a boolean, "%s" given.', $actionName, $entityName, \gettype($actionConfig['ask_confirm'])));
                }

                $backendConfig['entities'][$entityName]['list']['batch_actions'][$actionName] = $actionConfig;
            }
        }

        return $backendConfig;
    }

    /**
     * Returns the default configuration for all the built-in actions of the
     * given view, including the actions which are not enabled by default for
     * that view (e.g. the 'show' action for the 'list' view).
     *
     * @param string $view
     *
     * @return array
     */
    private function getDefaultActionsConfig($view)
    {
        $actions = $this->doNormalizeActionsConfig([
            'delete' => ['name' => 'delete', 'label' => 'action.delete', 'icon' => 'trash-o', 'css_class' => 'btn text-danger'],
            'edit' => ['name' => 'edit', 'label' => 'action.edit', 'icon' => 'edit', 'css_class' => 'btn btn-primary'],
            'new' => ['name' => 'new', 'label' => 'action.new', 'css_class' => 'btn btn-primary'],
            'search' => ['name' => 'search', 'label' => 'action.search'],
            'show' => ['name' => 'show', 'label' => 'action.show'],
            'list' => ['name' => 'list', 'label' => 'action.list', 'css_class' => 'btn btn-link pr-0'],
        ]);

        // minor tweaks for some action + view combinations
        if ('list' === $view) {
            $actions['delete']['icon'] = null;
            $actions['delete']['css_class'] = 'text-danger';
            $actions['edit']['icon'] = null;
            $actions['edit']['css_class'] = '';
            $actions['list']['css_class'] = '';
        }

        return $actions;
    }

    /**
     * Returns the built-in actions defined by EasyAdmin for the given view.
     * This allows to provide some nice defaults for backends that don't
     * define their own actions.
     *
     * @param string $view
     *
     * @return array
     */
    private function getDefaultActions($view)
    {
        $defaultActions = [];
        $defaultActionsConfig = $this->getDefaultActionsConfig($view);

        // actions are displayed in the same order as defined in this array
        $actionsEnabledByView = [
            'edit' => ['delete', 'list'],
            'list' => ['edit', 'delete', 'new', 'search'],
            'new' => ['list'],
            'show' => ['edit', 'delete', 'list'],
        ];

        foreach ($actionsEnabledByView[$view] as $actionName) {
            $defaultActions[$actionName] = $defaultActionsConfig[$actionName];
        }

        return $defaultActions;
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
     * (author: Bernhard Schussek <bschussek@gmail.com>).
     *
     * @param string $content
     *
     * @return string
     */
    private function humanizeString($content)
    {
        return ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $content))));
    }

    private function reorderArrayItems(array $originalArray, array $newKeyOrder)
    {
        $newArray = [];
        foreach ($newKeyOrder as $key) {
            if (isset($originalArray[$key])) {
                $newArray[$key] = $originalArray[$key];
            }
        }

        $missingKeys = array_diff(array_keys($originalArray), array_keys($newArray));
        foreach ($missingKeys as $key) {
            $newArray[$key] = $originalArray[$key];
        }

        return $newArray;
    }
}
