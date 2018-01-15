<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Twig;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Router\EasyAdminRouter;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Defines the filters and functions used to render the bundle's templates.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EasyAdminTwigExtension extends \Twig_Extension
{
    /** @var ConfigManager */
    private $configManager;
    /** @var PropertyAccessor */
    private $propertyAccessor;
    /** @var EasyAdminRouter */
    private $easyAdminRouter;
    /** @var bool */
    private $debug;
    private $logoutUrlGenerator;

    public function __construct(ConfigManager $configManager, PropertyAccessor $propertyAccessor, EasyAdminRouter $easyAdminRouter, $debug = false, $logoutUrlGenerator)
    {
        $this->configManager = $configManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->easyAdminRouter = $easyAdminRouter;
        $this->debug = $debug;
        $this->logoutUrlGenerator = $logoutUrlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('easyadmin_render_field_for_*_view', array($this, 'renderEntityField'), array('is_safe' => array('html'), 'needs_environment' => true)),
            new \Twig_SimpleFunction('easyadmin_config', array($this, 'getBackendConfiguration')),
            new \Twig_SimpleFunction('easyadmin_entity', array($this, 'getEntityConfiguration')),
            new \Twig_SimpleFunction('easyadmin_path', array($this, 'getEntityPath')),
            new \Twig_SimpleFunction('easyadmin_action_is_enabled', array($this, 'isActionEnabled')),
            new \Twig_SimpleFunction('easyadmin_action_is_enabled_for_*_view', array($this, 'isActionEnabled')),
            new \Twig_SimpleFunction('easyadmin_get_action', array($this, 'getActionConfiguration')),
            new \Twig_SimpleFunction('easyadmin_get_action_for_*_view', array($this, 'getActionConfiguration')),
            new \Twig_SimpleFunction('easyadmin_get_actions_for_*_item', array($this, 'getActionsForItem')),
            new \Twig_SimpleFunction('easyadmin_logout_path', array($this, 'getLogoutPath')),
        );
    }

    /**
     * {@inheritdoc}
     */
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
        return $this->configManager->getBackendConfig($key);
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
            ? $this->configManager->getEntityConfig($entityName)
            : null;
    }

    /**
     * @param object|string $entity
     * @param string        $action
     * @param array         $parameters
     *
     * @return string
     */
    public function getEntityPath($entity, $action, array $parameters = array())
    {
        return $this->easyAdminRouter->generate($entity, $action, $parameters);
    }

    /**
     * Renders the value stored in a property/field of the given entity. This
     * function contains a lot of code protections to avoid errors when the
     * property doesn't exist or its value is not accessible. This ensures that
     * the function never generates a warning or error message when calling it.
     *
     * @param \Twig_Environment $twig
     * @param string            $view          The view in which the item is being rendered
     * @param string            $entityName    The name of the entity associated with the item
     * @param object            $item          The item which is being rendered
     * @param array             $fieldMetadata The metadata of the actual field being rendered
     *
     * @return string
     *
     * @throws \Exception
     */
    public function renderEntityField(\Twig_Environment $twig, $view, $entityName, $item, array $fieldMetadata)
    {
        $entityConfiguration = $this->configManager->getEntityConfig($entityName);
        $hasCustomTemplate = 0 !== strpos($fieldMetadata['template'], '@EasyAdmin/');
        $templateParameters = array();

        try {
            $templateParameters = $this->getTemplateParameters($entityName, $view, $fieldMetadata, $item);

            // if the field defines a custom template, render it (no matter if the value is null or inaccessible)
            if ($hasCustomTemplate) {
                return $twig->render($fieldMetadata['template'], $templateParameters);
            }

            if (false === $templateParameters['is_accessible']) {
                return $twig->render($entityConfiguration['templates']['label_inaccessible'], $templateParameters);
            }

            if (null === $templateParameters['value']) {
                return $twig->render($entityConfiguration['templates']['label_null'], $templateParameters);
            }

            if (empty($templateParameters['value']) && in_array($fieldMetadata['dataType'], array('image', 'file', 'array', 'simple_array'))) {
                return $twig->render($templateParameters['entity_config']['templates']['label_empty'], $templateParameters);
            }

            return $twig->render($fieldMetadata['template'], $templateParameters);
        } catch (\Exception $e) {
            if ($this->debug) {
                throw $e;
            }

            return $twig->render($entityConfiguration['templates']['label_undefined'], $templateParameters);
        }
    }

    private function getTemplateParameters($entityName, $view, array $fieldMetadata, $item)
    {
        $fieldName = $fieldMetadata['property'];
        $fieldType = $fieldMetadata['dataType'];

        $parameters = array(
            'backend_config' => $this->getBackendConfiguration(),
            'entity_config' => $this->configManager->getEntityConfig($entityName),
            'field_options' => $fieldMetadata,
            'item' => $item,
            'view' => $view,
        );

        // the try..catch block is required because we can't use
        // $propertyAccessor->isReadable(), which is unavailable in Symfony 2.3
        try {
            $parameters['value'] = $this->propertyAccessor->getValue($item, $fieldName);
            $parameters['is_accessible'] = true;
        } catch (\Exception $e) {
            $parameters['value'] = null;
            $parameters['is_accessible'] = false;
        }

        if ('image' === $fieldType) {
            $parameters = $this->addImageFieldParameters($parameters);
        }

        if ('file' === $fieldType) {
            $parameters = $this->addFileFieldParameters($parameters);
        }

        if ('association' === $fieldType) {
            $parameters = $this->addAssociationFieldParameters($parameters);
        }

        if (true === $fieldMetadata['virtual']) {
            // when a virtual field doesn't define it's type, consider it a string
            if (null === $parameters['field_options']['dataType']) {
                $parameters['value'] = (string) $parameters['value'];
            }
        }

        return $parameters;
    }

    private function addImageFieldParameters(array $templateParameters)
    {
        // add the base path only to images that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null !== $templateParameters['value'] && 0 === preg_match('/^(http[s]?|\/\/)/i', $templateParameters['value'])) {
            $templateParameters['value'] = isset($templateParameters['field_options']['base_path'])
                ? rtrim($templateParameters['field_options']['base_path'], '/').'/'.ltrim($templateParameters['value'], '/')
                : '/'.ltrim($templateParameters['value'], '/');
        }

        $templateParameters['uuid'] = md5($templateParameters['value']);

        return $templateParameters;
    }

    private function addFileFieldParameters(array $templateParameters)
    {
        // add the base path only to files that are not absolute URLs (http or https) or protocol-relative URLs (//)
        if (null !== $templateParameters['value'] && 0 === preg_match('/^(http[s]?|\/\/)/i', $templateParameters['value'])) {
            $templateParameters['value'] = isset($templateParameters['field_options']['base_path'])
                ? rtrim($templateParameters['field_options']['base_path'], '/').'/'.ltrim($templateParameters['value'], '/')
                : '/'.ltrim($templateParameters['value'], '/');
        }

        $templateParameters['filename'] = isset($templateParameters['field_options']['filename']) ? $templateParameters['field_options']['filename'] : basename($templateParameters['value']);

        return $templateParameters;
    }

    private function addAssociationFieldParameters(array $templateParameters)
    {
        $targetEntityConfig = $this->configManager->getEntityConfigByClass($templateParameters['field_options']['targetEntity']);
        // the associated entity is not managed by EasyAdmin
        if (null === $targetEntityConfig) {
            return $templateParameters;
        }

        $isShowActionAllowed = !in_array('show', $targetEntityConfig['disabled_actions']);

        if ($templateParameters['field_options']['associationType'] & ClassMetadata::TO_ONE) {
            // the try..catch block is required because we can't use
            // $propertyAccessor->isReadable(), which is unavailable in Symfony 2.3
            try {
                $primaryKeyValue = $this->propertyAccessor->getValue($templateParameters['value'], $targetEntityConfig['primary_key_field_name']);
            } catch (\Exception $e) {
                $primaryKeyValue = null;
            }

            // get the string representation of the associated *-to-one entity
            if (method_exists($templateParameters['value'], '__toString')) {
                $templateParameters['value'] = (string) $templateParameters['value'];
            } elseif (null !== $primaryKeyValue) {
                $templateParameters['value'] = sprintf('%s #%s', $targetEntityConfig['name'], $primaryKeyValue);
            } else {
                $templateParameters['value'] = $this->getClassShortName($templateParameters['field_options']['targetEntity']);
            }

            // if the associated entity is managed by EasyAdmin, and the "show"
            // action is enabled for the associated entity, display a link to it
            if (null !== $targetEntityConfig && null !== $primaryKeyValue && $isShowActionAllowed) {
                $templateParameters['link_parameters'] = array(
                    'action' => 'show',
                    'entity' => $targetEntityConfig['name'],
                    // casting to string is needed because entities can use objects as primary keys
                    'id' => (string) $primaryKeyValue,
                );
            }
        }

        if ($templateParameters['field_options']['associationType'] & ClassMetadata::TO_MANY) {
            // if the associated entity is managed by EasyAdmin, and the "show"
            // action is enabled for the associated entity, display a link to it
            if (null !== $targetEntityConfig && $isShowActionAllowed) {
                $templateParameters['link_parameters'] = array(
                    'action' => 'show',
                    'entity' => $targetEntityConfig['name'],
                    'primary_key_name' => $targetEntityConfig['primary_key_field_name'],
                );
            }
        }

        return $templateParameters;
    }

    /**
     * Checks whether the given 'action' is enabled for the given 'entity'.
     *
     * @param string $view
     * @param string $action
     * @param string $entityName
     *
     * @return bool
     */
    public function isActionEnabled($view, $action, $entityName)
    {
        return $this->configManager->isActionEnabled($entityName, $view, $action);
    }

    /**
     * Returns the full action configuration for the given 'entity' and 'view'.
     *
     * @param string $view
     * @param string $action
     * @param string $entityName
     *
     * @return array
     */
    public function getActionConfiguration($view, $action, $entityName)
    {
        return $this->configManager->getActionConfig($entityName, $view, $action);
    }

    /**
     * Returns the actions configured for each item displayed in the given view.
     * This method is needed because some actions are displayed globally for the
     * entire view (e.g. 'new' action in 'list' view).
     *
     * @param string $view
     * @param string $entityName
     *
     * @return array
     */
    public function getActionsForItem($view, $entityName)
    {
        try {
            $entityConfig = $this->configManager->getEntityConfig($entityName);
        } catch (\Exception $e) {
            return array();
        }

        $disabledActions = $entityConfig['disabled_actions'];
        $viewActions = $entityConfig[$view]['actions'];

        $actionsExcludedForItems = array(
            'list' => array('new', 'search'),
            'edit' => array(),
            'new' => array(),
            'show' => array(),
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
     *
     * @return string
     */
    public function truncateText(\Twig_Environment $env, $value, $length = 64, $preserve = false, $separator = '...')
    {
        try {
            $value = (string) $value;
        } catch (\Exception $e) {
            $value = '';
        }

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

    /**
     * This reimplementation of Symfony's logout_path() helper is needed because
     * when no arguments are passed to the getLogoutPath(), it's common to get
     * exceptions and there is no way to recover from them in a Twig template.
     */
    public function getLogoutPath()
    {
        if (null === $this->logoutUrlGenerator) {
            return;
        }

        try {
            return $this->logoutUrlGenerator->getLogoutPath();
        } catch (\Exception $e) {
            return;
        }
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
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension', 'JavierEguiluz\Bundle\EasyAdminBundle\Twig\EasyAdminTwigExtension', false);
