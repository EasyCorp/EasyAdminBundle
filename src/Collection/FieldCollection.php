<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

final class FieldCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var FieldDto[] */
    private $fields;

    /**
     * @param FieldInterface[]|string[] $fields
     */
    private function __construct(iterable $fields)
    {
        $this->fields = $this->processFields($fields);
    }

    /**
     * @param FieldInterface[]|string[] $fields
     */
    public static function new(iterable $fields)
    {
        return new self($fields);
    }

    public function get(string $fieldName): ?FieldDto
    {
        return $this->fields[$fieldName] ?? null;
    }

    public function set(string $fieldName, FieldDto $field): void
    {
        $this->fields[$fieldName] = $field;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->fields);
    }

    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->fields[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    public function count()
    {
        return count($this->fields);
    }

    /**
     * @return \ArrayIterator|\Traversable|FieldDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * @param FieldInterface[] $fields
     * @return FieldDto[]
     */
    private function processFields(iterable $fields): array
    {
        $dtos = [];

        // for DX reasons, fields can be configured as a FieldInterface object and
        // as a simple string with the name of the Doctrine property
        foreach ($fields as $fieldObjectOrPropertyName) {
            if ($fieldObjectOrPropertyName instanceof FieldInterface) {
                $field = $fieldObjectOrPropertyName;
                $dto = $field->getAsDto();
                $dtos[$dto->getName()] = $dto;
            } else {
                $propertyName = $fieldObjectOrPropertyName;
                $dtos[$propertyName] = Field::new($propertyName)->getAsDto();
            }
        }

        return $dtos;
    }
}
