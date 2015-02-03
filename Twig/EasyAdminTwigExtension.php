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
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('truncate_entity_field', array($this, 'truncateEntityField'), array('needs_environment' => true)),
        );
    }

    public function displayEntityField($entity, $fieldName, array $fieldMetadata)
    {
        $value = $this->getEntityProperty($entity, $fieldName);

        if ('__inaccessible_doctrine_property__' === $value) {
            return new \Twig_Markup('<span class="label label-danger" title="Method does not exist or property is not public">inaccessible</span>', 'UTF-8');
        }

        try {
            $fieldType = $fieldMetadata['type'];

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

            if (in_array($fieldType, array('boolean'))) {
                return new \Twig_Markup(sprintf('<span class="label label-%s">%s</span>',
                    true === $value ? 'success' : 'danger',
                    true === $value ? 'YES' : 'NO'
                ), 'UTF-8');
            }

            if (in_array($fieldType, array('array', 'simple_array'))) {
                return implode(', ', $value);
            }

            if (in_array($fieldType, array('string', 'text'))) {
                return $value;
            }

            if (in_array($fieldType, array('bigint', 'integer', 'smallint', 'decimal', 'float'))) {
                return isset($fieldMetadata['format']) ? sprintf($fieldMetadata['format'], $value) : number_format($value);
            }

            if (in_array($fieldType, array('association'))) {
                $associatedEntityClassParts = explode('\\', $fieldMetadata['targetEntity']);
                $associatedEntityClassName = end($associatedEntityClassParts);

                if ($value instanceof PersistentCollection) {
                    return new \Twig_Markup(sprintf('<span class="badge">%d</span>', count($value), $associatedEntityClassName), 'UTF-8');
                }

                try {
                    $associatedEntityPrimaryKey = $this->configurator->getEntityConfiguration($associatedEntityClassName)['primary_key_field_name'];
                } catch (\Exception $e) {
                    // if the entity isn't managed by EasyAdmin, don't link to it and just display its raw value
                    return $value;
                }

                $primaryKeyGetter = 'get'.ucfirst($associatedEntityPrimaryKey);
                if (method_exists($value, $primaryKeyGetter)) {
                    $associatedEntityUrl = $this->urlGenerator->generate('admin', array('entity' => $associatedEntityClassName, 'action' => 'show', 'id' => $value->$primaryKeyGetter()));
                    // escaping is done manually in order to include this content in a Twig_Markup object
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
     * It looks for any entity method able to get the value of the given property.
     * First it looks for the methods: getProperty(), isProperty(), property() and hasProperty()
     * Then, it looks if 'property' exists as an accessible property in the entity.
     */
    private function getEntityProperty($entity, $property)
    {
        // first, look for common method names
        $fieldGetterMethods = array(
            'get'.ucfirst($property),
            'is'.ucfirst($property),
            $property,
            'has'.ucfirst($property),
        );

        foreach ($fieldGetterMethods as $method) {
            if (method_exists($entity, $method)) {
                return $entity->{$method}();
            }
        }

        // if no method exists, look for a public property
        if (!property_exists($entity, $property)) {
            return '__inaccessible_doctrine_property__';
        }

        $propertyMetadata = new \ReflectionProperty($entity, $property);
        if (!$propertyMetadata->isPublic()) {
            return '__inaccessible_doctrine_property__';
        }

        return $entity->{$property};
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
