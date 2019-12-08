<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

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
     * @return PropertyDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->propertiesDto);
    }
}
