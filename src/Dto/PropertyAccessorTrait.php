<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * Allows to get the value of any object property.
 */
trait PropertyAccessorTrait
{
    public function get(string $propertyName)
    {
        if (!property_exists($this, $propertyName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" property is not defined in the "%s" class. Valid property names are: %s', $propertyName, static::class, implode(', ', array_keys(get_object_vars($this)))));
        }

        return $this->{$propertyName};
    }
}
