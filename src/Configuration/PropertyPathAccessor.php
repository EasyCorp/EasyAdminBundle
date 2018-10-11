<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class PropertyPathAccessor
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param array  $backendConfig
     * @param string $propertyPath
     *
     * @return mixed
     */
    public function getValue(array $backendConfig, string $propertyPath)
    {
        // turns 'design.menu' into '[design][menu]', the format required by PropertyAccess
        $propertyPath = '['.str_replace('.', '][', $propertyPath).']';

        return $this->propertyAccessor->getValue($backendConfig, $propertyPath);
    }
}
