<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;

final class PropertyDtoCollection implements \IteratorAggregate
{
    /** @var PropertyDto[] */
    private $propertiesDto;

    private function __construct()
    {
    }

    public static function new(array $propertiesDto = null): self
    {
        $collection = new self();
        $collection->propertiesDto = $propertiesDto;

        return $collection;
    }

    /**
     * @param \EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyInterface[] $propertiesConfig
     */
    public static function fromPropertiesConfig(array $propertiesConfig): self
    {
        $propertiesDto = [];
        foreach ($propertiesConfig as $propertyConfig) {
            $propertiesDto[] = $propertyConfig->getAsDto();
        }

        return self::new($propertiesDto);
    }

    /**
     * @return PropertyDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->propertiesDto);
    }
}
