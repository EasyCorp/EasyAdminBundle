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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;

class EasyAdminTwigExtension extends \Twig_Extension
{
    private $urlGenerator;
    private $configurator;

    public function __construct(UrlGeneratorInterface $urlGenerator, Configurator $configurator)
    {
        $this->urlGenerator = $urlGenerator;
        $this->configurator = $configurator;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('easyadmin_render_field_for_*_view', array($this, 'renderEntityField')),
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
        );
    }

    /**
     * Returns the entire backend configuration or the value corresponding to
     * the provided key. The dots of the key are automatically transformed into
     * nested keys. Example: 'assets.css' => $config['assets']['css']
     *
     * @param  string|null $key
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
     * @param  string $entityName
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
     * @param  array $entity
     * @param  array  $fieldMetadata
     * @return mixed
     */
    public function renderEntityField($view, $entity, array $fieldMetadata)
    {
        if (!$fieldMetadata['canBeGet']) {
            return new \Twig_Markup('<span class="label label-danger" title="Getter method does not exist or property is not public">inaccessible</span>', 'UTF-8');
        }

        $fieldName = $fieldMetadata['property'];
        $value = (null !== $getter = $fieldMetadata['getter']) ? $entity->{$getter}() : $entity->{$fieldName};

        try {
            $fieldType = $fieldMetadata['dataType'];

            if (null === $value) {
                return new \Twig_Markup('<span class="label">NULL</span>', 'UTF-8');
            }

            // when a virtual field doesn't define it's type, consider it a string
            if (true === $fieldMetadata['virtual'] && null === $fieldType) {
                return strval($value);
            }

            if ('id' === $fieldName) {
                // return the ID value as is to avoid number formatting
                return $value;
            }

            if (in_array($fieldType, array('date'))) {
                return $value->format($fieldMetadata['format']);
            }

            if (in_array($fieldType, array('datetime', 'datetimetz'))) {
                return $value->format($fieldMetadata['format']);
            }

            if (in_array($fieldType, array('time'))) {
                return $value->format($fieldMetadata['format']);
            }

            if (in_array($fieldType, array('toggle'))) {
                return new \Twig_Markup(sprintf('<input type="checkbox" %s data-toggle="toggle" data-size="mini" data-onstyle="success" data-offstyle="danger" data-on="YES" data-off="NO">',
                    true === $value ? 'checked' : ''
                ), 'UTF-8');
            }

            if (in_array($fieldType, array('boolean'))) {
                return new \Twig_Markup(sprintf('<span class="label label-%s">%s</span>',
                    true === $value ? 'success' : 'danger',
                    true === $value ? 'YES' : 'NO'
                ), 'UTF-8');
            }

            if (in_array($fieldType, array('array', 'simple_array'))) {
                return empty($value)
                    ? new \Twig_Markup('<span class="label label-empty">EMPTY</span>', 'UTF-8')
                    : implode(', ', $value);
            }

            if (in_array($fieldType, array('string', 'text'))) {
                return (string) $value;
            }

            if (in_array($fieldType, array('bigint', 'integer', 'smallint', 'decimal', 'float'))) {
                return isset($fieldMetadata['format']) ? sprintf($fieldMetadata['format'], $value) : number_format($value);
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

                return new \Twig_Markup(sprintf('<img src="%s">', $imageUrl), 'UTF-8');
            }

            if (in_array($fieldType, array('association'))) {
                if ($value instanceof PersistentCollection) {
                    return new \Twig_Markup(sprintf('<span class="badge">%d</span>', count($value)), 'UTF-8');
                }

                $associatedEntityClassParts = explode('\\', $fieldMetadata['targetEntity']);
                $associatedEntityClassName = end($associatedEntityClassParts);

                try {
                    $associatedEntityConfig = $this->configurator->getEntityConfiguration($associatedEntityClassName);
                    $associatedEntityPrimaryKey = $associatedEntityConfig['primary_key_field_name'];
                } catch (\Exception $e) {
                    // if the entity isn't managed by EasyAdmin, don't link to it and just display its raw value
                    return $value;
                }

                $primaryKeyGetter = 'get'.ucfirst($associatedEntityPrimaryKey);
                if (method_exists($value, $primaryKeyGetter)) {
                    $associatedEntityUrl = $this->urlGenerator->generate('admin', array('entity' => $associatedEntityClassName, 'action' => 'show', 'view' => $view, 'id' => $value->$primaryKeyGetter()));
                    // escaping is done manually in order to include this content inside a Twig_Markup object
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    // ideally we'd use the 'truncateEntityField' method, but it's cumbersome to invoke it from here
                    $associatedEntityValue = strlen($value) > 64 ? substr($value, 0, 64).'...' : $value;

                    return new \Twig_Markup(sprintf('<a href="%s">%s</a>', $associatedEntityUrl, $associatedEntityValue), 'UTF-8');
                }

                return $value;
            }
        } catch (\Exception $e) {
            return '';
        }

        return '';
    }

    /**
     * Checks whether the given 'action' is enabled for the given 'entity'.
     *
     * @param  string  $action
     * @param  string  $entityName
     * @return boolean
     */
    public function isActionEnabled($view, $action, $entityName)
    {
        $entityConfiguration = $this->configurator->getEntityConfiguration($entityName);

        return array_key_exists($action, $entityConfiguration[$view]['actions']);
    }

    /**
     * Checks whether the given 'action' is enabled for the given 'entity'.
     *
     * @param  string  $action
     * @param  string  $entityName
     * @return boolean
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
     *
     * @param  string $entityName
     * @return array
     */
    public function getActionsForItem($view, $entityName)
    {
        $entityConfiguration = $this->configurator->getEntityConfiguration($entityName);
        $configuredActions = $entityConfiguration[$view]['actions'];
        $excludedActionsPerView = array(
            'list' => array('delete', 'list', 'new', 'search'),
            'edit' => array('list', 'delete'),
            'new'  => array('list'),
            'show' => array('list', 'delete'),
        );
        $excludedActions = $excludedActionsPerView[$view];

        return array_filter($configuredActions, function($action) use ($excludedActions) {
            return !in_array($action['name'], $excludedActions);
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

    public function getName()
    {
        return 'easyadmin_extension';
    }
}
