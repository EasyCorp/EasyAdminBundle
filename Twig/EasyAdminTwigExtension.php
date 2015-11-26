<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Twig;

use Doctrine\ORM\PersistentCollection;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;

/**
 * Defines the filters and functions used to render the bundle's templates.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminTwigExtension extends \Twig_Extension
{
    private $configurator;
    private $debug;

    public function __construct(Configurator $configurator, $debug = false)
    {
        $this->configurator = $configurator;
        $this->debug = $debug;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('easyadmin_render_field_for_*_view', array($this, 'renderEntityField'), array('is_safe' => array('html'), 'needs_environment' => true)),
            new \Twig_SimpleFunction('easyadmin_config', array($this, 'getBackendConfiguration')),
            new \Twig_SimpleFunction('easyadmin_entity', array($this, 'getEntityConfiguration')),
            new \Twig_SimpleFunction('easyadmin_action_is_enabled_for_*_view', array($this, 'isActionEnabled')),
            new \Twig_SimpleFunction('easyadmin_get_action_for_*_view', array($this, 'getActionConfiguration')),
            new \Twig_SimpleFunction('easyadmin_get_actions_for_*_item', array($this, 'getActionsForItem')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('easyadmin_truncate', array($this, 'truncateText'), array('needs_environment' => true)),
            new \Twig_SimpleFilter('easyadmin_urldecode', 'urldecode'),
        );
    }

    /**
     * Returns the entire backend configuration or the value corresponding to
     * the provided key. The dots of the key are automatically transformed into
     * nested keys. Example: 'assets.css' => $config['assets']['css'].
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getBackendConfiguration($key = null)
    {
        $config = $this->configurator->getBackendConfig();

        if (!empty($key)) {
            $parts = explode('.', $key);

            foreach ($parts as $part) {
                if (!isset($config[$part])) {
                    $config = null;
                    break;
                }
                $config = $config[$part];
            }
        }

        return $config;
    }

    /**
     * Returns the entire configuration of the given entity.
     *
     * @param string $entityName
     *
     * @return array|null
     */
    public function getEntityConfiguration($entityName)
    {
        return null !== $this->getBackendConfiguration('entities.'.$entityName)
            ? $this->configurator->getEntityConfiguration($entityName)
            : null;
    }

    /**
     * Renders the value stored in a property/field of the given entity. This
     * function contains a lot of code protections to avoid errors when the
     * property doesn't exist or its value is not accessible. This ensures that
     * the function never generates a warning or error message when calling it.
     *
     * @param string $view          The view in which the item is being rendered
     * @param string $entityName    The name of the entity associated with the item
     * @param object $item          The item which is being rendered
     * @param array  $fieldMetadata The metadata of the actual field being rendered
     *
     * @return string
     */
    public function renderEntityField(\Twig_Environment $twig, $view, $entityName, $item, array $fieldMetadata)
    {
        $entityConfiguration = $this->configurator->getEntityConfiguration($entityName);

        if (!$fieldMetadata['isReadable']) {
            return $twig->render($entityConfiguration['templates']['label_inaccessible'], array('view' => $view));
        }

        $fieldName = $fieldMetadata['property'];
        $value = (null !== $getter = $fieldMetadata['getter']) ? $item->{$getter}() : $item->{$fieldName};

        try {
            $fieldType = $fieldMetadata['dataType'];
            $templateParameters = array(
                'field_options' => $fieldMetadata,
                'item' => $item,
                'value' => $value,
                'view' => $view,
            );

            if (null === $value) {
                return $twig->render($entityConfiguration['templates']['label_null'], $templateParameters);
            }

            // when a virtual field doesn't define it's type, consider it a string
            if (true === $fieldMetadata['virtual'] && null === $fieldType) {
                $templateParameters['value'] = strval($value);
            }

            if (in_array($fieldType, array('image'))) {
                // absolute URLs (http or https) and protocol-relative URLs (//) are rendered unmodified
                if (1 === preg_match('/^(http[s]?|\/\/).*/i', $value)) {
                    $imageUrl = $value;
                } else {
                    $imageUrl = isset($fieldMetadata['base_path'])
                        ? rtrim($fieldMetadata['base_path'], '/').'/'.ltrim($value, '/')
                        : '/'.ltrim($value, '/');
                }
                $templateParameters['value'] = $imageUrl;
            }

            if (in_array($fieldType, array('array', 'simple_array')) && empty($value)) {
                return $twig->render($entityConfiguration['templates']['label_empty'], $templateParameters);
            }

            if (in_array($fieldType, array('association')) && !$value instanceof PersistentCollection) {
                $targetEntityClassName = $this->getClassShortName($fieldMetadata['targetEntity']);
                $targetEntityConfig = $this->getEntityConfiguration($targetEntityClassName);
                $targetEntityPrimaryKeyGetter = (null !== $targetEntityConfig) ? 'get'.ucfirst($targetEntityConfig['primary_key_field_name']) : null;

                // get the most appropriate string representation for the
                // associated value (this depends on the target entity methods)
                if (method_exists($value, '__toString')) {
                    $templateParameters['value'] = (string) $value;
                } elseif (method_exists($value, $targetEntityPrimaryKeyGetter)) {
                    $templateParameters['value'] = sprintf('%s #%s', $targetEntityConfig['name'], $value->$targetEntityPrimaryKeyGetter());
                } else {
                    $templateParameters['value'] = $this->getClassShortName(get_class($value));
                }

                // if the target entity has a primary key getter, it's displayed
                // as a link pointing to its 'show' view
                if (method_exists($value, $targetEntityPrimaryKeyGetter)) {
                    $templateParameters['link_parameters'] = array('entity' => $targetEntityConfig['name'], 'action' => 'show', 'view' => $view, 'id' => $value->$targetEntityPrimaryKeyGetter());
                }
            }

            return $twig->render($fieldMetadata['template'], $templateParameters);
        } catch (\Exception $e) {
            if ($this->debug) {
                throw $e;
            }

            return $twig->render($entityConfiguration['templates']['label_undefined'], array('view' => $view));
        }
    }

    /**
     * Checks whether the given 'action' is enabled for the given 'entity'.
     *
     * @param string $action
     * @param string $entityName
     *
     * @return bool
     */
    public function isActionEnabled($view, $action, $entityName)
    {
        $entityConfiguration = $this->configurator->getEntityConfiguration($entityName);

        return !in_array($action, $entityConfiguration['disabled_actions'])
            && array_key_exists($action, $entityConfiguration[$view]['actions']);
    }

    /**
     * Returns the full action configuration for the given 'entity' and 'view'.
     *
     * @param string $action
     * @param string $entityName
     *
     * @return bool
     */
    public function getActionConfiguration($view, $action, $entityName)
    {
        $entityConfiguration = $this->configurator->getEntityConfiguration($entityName);

        return isset($entityConfiguration[$view]['actions'][$action])
            ? $entityConfiguration[$view]['actions'][$action]
            : array();
    }

    /**
     * Returns the actions configured for each item displayed in the given view.
     * This method is needed because some actions are displayed globally for the
     * entire view (e.g. 'new' action in 'list' view) and other default actions
     * are treated in a special way (e.g. 'delete' action in 'edit'/'show' views).
     *
     * @param string $entityName
     *
     * @return array
     */
    public function getActionsForItem($view, $entityName)
    {
        $entityConfiguration = $this->configurator->getEntityConfiguration($entityName);
        $disabledActions = $entityConfiguration['disabled_actions'];
        $viewActions = $entityConfiguration[$view]['actions'];

        // Each view displays some actions in special ways (e.g. the 'new' button
        // in the 'list' view). Those special actions shouldn't be displayed for
        // each item as a regular action.
        $actionsExcludedForItems = array(
            'list' => array('delete', 'list', 'new', 'search'),
            'edit' => array('list', 'delete'),
            'new' => array('list'),
            'show' => array('list', 'delete'),
        );
        $excludedActions = $actionsExcludedForItems[$view];

        return array_filter($viewActions, function ($action) use ($excludedActions, $disabledActions) {
            return !in_array($action['name'], $excludedActions) && !in_array($action['name'], $disabledActions);
        });
    }

    /*
     * Copied from the official Text Twig extension.
     *
     * code: https://github.com/twigphp/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Text.php
     * author: Henrik Bjornskov <hb@peytz.dk>
     * copyright holder: (c) 2009 Fabien Potencier
     */
    public function truncateText(\Twig_Environment $env, $value, $length = 64, $preserve = false, $separator = '...')
    {
        if (function_exists('mb_get_info')) {
            if (mb_strlen($value, $env->getCharset()) > $length) {
                if ($preserve) {
                    // If breakpoint is on the last word, return the value without separator.
                    if (false === ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
                        return $value;
                    }

                    $length = $breakpoint;
                }

                return rtrim(mb_substr($value, 0, $length, $env->getCharset())).$separator;
            }

            return $value;
        }

        if (strlen($value) > $length) {
            if ($preserve) {
                if (false !== ($breakpoint = strpos($value, ' ', $length))) {
                    $length = $breakpoint;
                }
            }

            return rtrim(substr($value, 0, $length)).$separator;
        }

        return $value;
    }

    /**
     * It returns the last part of the fully qualified class name
     * (e.g. 'AppBundle\Entity\User' -> 'User').
     *
     * @param string $fqcn
     *
     * @return string
     */
    private function getClassShortName($fqcn)
    {
        $classParts = explode('\\', $fqcn);
        $className = end($classParts);

        return $className;
    }

    public function getName()
    {
        return 'easyadmin_extension';
    }
}
