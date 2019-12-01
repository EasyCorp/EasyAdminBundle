<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * Allows to modify the value of any object property.
 */
trait PropertyModifierTrait
{
    /**
     * @param array $newPropertyValues ['propertyName' => $propertyValue, ...]
     */
    public function withProperties(array $newPropertyValues): self
    {
        foreach ($newPropertyValues as $propertyName => $propertyValue) {
            if (!property_exists($this, $propertyName)) {
                throw new \InvalidArgumentException(sprintf('The "%s" option is not a valid action context option name. Valid option names are: %s', $propertyName, implode(', ', array_keys(get_object_vars($this)))));
            }

            $this->{$propertyName} = $propertyValue;
        }

        return $this;
    }
}
