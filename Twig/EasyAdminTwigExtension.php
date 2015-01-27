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

class EasyAdminTwigExtension extends \Twig_Extension
{
    const DATE_FORMAT = 'F j, Y H:i';
    const TIME_FORMAT = 'H:i:s';

    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions()
    {
        return array(
            'entity_field' => new \Twig_Function_Method($this, 'displayEntityField'),
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

            if ('id' === $fieldName) {
                // return the ID value as is to avoid number formatting
                return $value;
            }

            if (in_array($fieldType, array('date', 'datetime', 'datetimetz'))) {
                return $value->format(self::DATE_FORMAT);
            }

            if (in_array($fieldType, array('time'))) {
                return $value->format(self::TIME_FORMAT);
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
                return strlen($value) > 64 ? substr($value, 0, 64).'...' : $value;
            }

            if (in_array($fieldType, array('bigint', 'integer', 'smallint', 'decimal', 'float'))) {
                return number_format($value);
            }

            if (in_array($fieldType, array('association'))) {
                $associatedEntityClassParts = explode('\\', $fieldMetadata['targetEntity']);
                $associatedEntityClassName = end($associatedEntityClassParts);

                if ($value instanceof PersistentCollection) {
                    return new \Twig_Markup(sprintf('<span class="badge">%d</span>', count($value), $associatedEntityClassName), 'UTF-8');
                }

                if (method_exists($value, 'getId')) {
                    return new \Twig_Markup(sprintf('<a href="%s">%s</a>', $this->urlGenerator->generate('admin', array('entity' => $associatedEntityClassName, 'action' => 'show', 'id' => $value->getId())), $value), 'UTF-8');
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

    public function getName()
    {
        return 'easyadmin_extension';
    }
}
