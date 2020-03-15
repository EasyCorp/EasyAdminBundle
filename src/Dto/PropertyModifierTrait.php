<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * Allows to modify the value of any object field.
 */
trait PropertyModifierTrait
{
    /**
     * @param array $newPropertyValues ['propertyName' => $propertyValue, ...]
     */
    public function with(array $newPropertyValues): self
    {
        $clone = clone $this;

        foreach ($newPropertyValues as $propertyName => $propertyValue) {
            if (!property_exists($this, $propertyName)) {
                throw new \InvalidArgumentException(sprintf('The "%s" field is not defined in the "%s" class. Valid field names are: %s', $propertyName, static::class, implode(', ', array_keys(get_object_vars($this)))));
            }

            $clone->{$propertyName} = $propertyValue;
        }

        return $clone;
    }
}
