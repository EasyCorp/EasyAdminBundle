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
            new \Twig_SimpleFunction('entity_field', array($this, 'displayEntityField')),
            new \Twig_SimpleFunction('easyadmin_config', array($this, 'getEasyAdminConfig')),
            new \Twig_SimpleFunction('easyadmin_entity', array($this, 'getEntity')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('truncate_entity_field', array($this, 'truncateEntityField'), array('needs_environment' => true)),
        );
    }

    public function getEasyAdminConfig($path = null)
    {
        $config = $this->configurator->getBackendConfig();

        if (!empty($path)) {
            $parts = explode('.', $path);

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

    public function getEntity($entityName)
    {
        return null !== $this->getEasyAdminConfig('entities.' . $entityName)
            ? $this->configurator->getEntityConfiguration($entityName)
            : null;
    }

    public function displayEntityField($entity, array $fieldMetadata)
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
                    $associatedEntityUrl = $this->urlGenerator->generate('admin', array('entity' => $associatedEntityClassName, 'action' => 'show', 'id' => $value->$primaryKeyGetter()));
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

    /*
     * Copied from the official Text Twig extension.
     *
     * code: https://github.com/twigphp/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Text.php
     * author: Henrik Bjornskov <hb@peytz.dk>
     * copyright holder: (c) 2009 Fabien Potencier
     */
    public function truncateEntityField(\Twig_Environment $env, $value, $length = 64, $preserve = false, $separator = '...')
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
