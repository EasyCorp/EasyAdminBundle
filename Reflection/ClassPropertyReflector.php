<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Reflection;

use Doctrine\Common\Inflector\Inflector;

/**
 * Introspects information about the properties of the given class.
 */
class ClassPropertyReflector
{
    /**
     * Returns the name of the getter for the class property or null if there is none.
     *
     * @param string $classNamespace
     * @param string $propertyName
     *
     * @return string|null
     */
    public function getGetter($classNamespace, $propertyName)
    {
        $camelCasedPropertyName = Inflector::classify($propertyName);

        $getterMethods = array(
            'get'.ucfirst($propertyName),
            'is'.ucfirst($propertyName),
            $propertyName,
            'has'.ucfirst($propertyName),
            'get'.$camelCasedPropertyName,
            'is'.$camelCasedPropertyName,
            $camelCasedPropertyName,
            'has'.$camelCasedPropertyName,
        );

        return $this->getFirstExistingMethod($classNamespace, $getterMethods);
    }

    /**
     * Returns the name of the setter for the class property or null if there is none.
     *
     * @param string $classNamespace
     * @param string $propertyName
     *
     * @return string|null
     */
    public function getSetter($classNamespace, $propertyName)
    {
        $camelCasedPropertyName = Inflector::classify($propertyName);

        $setterMethods = array(
            'set'.ucfirst($propertyName),
            'setIs'.ucfirst($propertyName),
            'set'.$camelCasedPropertyName,
            'setIs'.$camelCasedPropertyName,
        );

        return $this->getFirstExistingMethod($classNamespace, $setterMethods);
    }

    /**
     * Given an array of method names, it returns the name of the first method
     * that exists for the given class. It returns null if none of the given
     * method exist.
     *
     * @param string   $classNamespace The fully qualified name of the class
     * @param string[] $methods
     *
     * @return string|null
     */
    private function getFirstExistingMethod($classNamespace, array $methods)
    {
        foreach ($methods as $method) {
            if (method_exists($classNamespace, $method)) {
                return $method;
            }
        }
    }

    /**
     * Returns 'true' if the class property is public (it exists and its scope is 'public').
     *
     * @param string $classNamespace The fully qualified name of the class
     * @param string $propertyName
     *
     * @return bool
     */
    public function isPublic($classNamespace, $propertyName)
    {
        if (!property_exists($classNamespace, $propertyName)) {
            return false;
        }

        $propertyMetadata = new \ReflectionProperty($classNamespace, $propertyName);

        return $propertyMetadata->isPublic();
    }
}
