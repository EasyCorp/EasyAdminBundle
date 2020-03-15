<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

final class FieldDtoCollection implements \IteratorAggregate
{
    /** @var FieldDto[] */
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
     * @param \EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface[] $fields
     */
    public static function fromFields(array $fields): self
    {
        $propertiesDto = [];
        foreach ($fields as $field) {
            $propertiesDto[] = $field->getAsDto();
        }

        return self::new($propertiesDto);
    }

    /**
     * @return FieldDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->propertiesDto);
    }
}
