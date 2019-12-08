<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

final class EntityDtoCollection implements \IteratorAggregate
{
    /** @var EntityDto[] */
    private $entitiesDto;

    private function __construct()
    {
    }

    public static function new(array $entitiesDto = null): self
    {
        $collection = new self();
        $collection->entitiesDto = $entitiesDto;

        return $collection;
    }

    /**
     * @return EntityDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->entitiesDto);
    }
}
