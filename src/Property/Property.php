<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;

final class Property implements PropertyConfigInterface
{
    use PropertyConfigTrait;

    public function __construct()
    {
        $this->type = 'generic';
        $this->templateName = 'property/generic';
    }

    /**
     * This method transforms the current object into any other object that implements
     * PropertyInterface. It's needed when using autoconfigurable properties, where
     * the user gives a Property instance but the application needs TextProperty, etc.
     */
    public function transformInto(string $propertyFqcn): PropertyConfigInterface
    {
        $objectProperties = get_object_vars($this);
        $newObjectInstance = $propertyFqcn::new($objectProperties['name']);

        $newObjectReflection = new \ReflectionObject($newObjectInstance);
        foreach ($objectProperties as $objectPropertyName => $objectPropertyValue) {
            // special read-only object properties managed by PHP. They cannot be set:
            // see https://stackoverflow.com/questions/9314593/cannot-set-read-only-property
            if (\in_array($objectPropertyName, ['name', 'class'])) {
                continue;
            }

            $objectProperty = $newObjectReflection->getProperty($objectPropertyName);
            $objectProperty->setAccessible(true);
            $objectProperty->setValue($newObjectInstance, $objectPropertyValue);
        }

        return $newObjectInstance;
    }
}
