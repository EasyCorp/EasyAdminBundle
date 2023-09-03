<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Collection\CollectionInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FieldCollection implements CollectionInterface
{
    /** @var FieldDtoInterface[] */
    private array $fields;

    /**
     * @param FieldInterface[]|string[] $fields
     */
    private function __construct(iterable $fields)
    {
        $this->fields = $this->processFields($fields);
    }

    public function __clone()
    {
        $clonedFields = [];
        foreach ($this->fields as $fieldDto) {
            $clonedFieldDto = clone $fieldDto;
            $clonedFields[$clonedFieldDto->getUniqueId()] = $clonedFieldDto;
        }

        $this->fields = $clonedFields;
    }

    /**
     * @param FieldInterface[]|string[] $fields
     */
    public static function new(iterable $fields): self
    {
        return new self($fields);
    }

    public function get(string $fieldUniqueId): ?FieldDtoInterface
    {
        return $this->fields[$fieldUniqueId] ?? null;
    }

    /**
     * It returns the first field associated to the given property or null if none found.
     * Some pages (index/detail) can render the same field more than once.
     * In those cases, this method always returns the first field occurrence.
     */
    public function getByProperty(string $propertyName): ?FieldDtoInterface
    {
        foreach ($this->fields as $field) {
            if ($propertyName === $field->getProperty()) {
                return $field;
            }
        }

        return null;
    }

    public function set(FieldDtoInterface $newOrUpdatedField): void
    {
        $this->fields[$newOrUpdatedField->getUniqueId()] = $newOrUpdatedField;
    }

    public function unset(FieldDtoInterface $removedField): void
    {
        unset($this->fields[$removedField->getUniqueId()]);
    }

    public function prepend(FieldDtoInterface $newField): void
    {
        $this->fields = array_merge([$newField->getUniqueId() => $newField], $this->fields);
    }

    public function first(): ?FieldDtoInterface
    {
        if (0 === \count($this->fields)) {
            return null;
        }

        return $this->fields[array_key_first($this->fields)];
    }

    public function isEmpty(): bool
    {
        return 0 === \count($this->fields);
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->fields);
    }

    public function offsetGet(mixed $offset): FieldDtoInterface
    {
        return $this->fields[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->fields[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->fields[$offset]);
    }

    public function count(): int
    {
        return \count($this->fields);
    }

    /**
     * @return \ArrayIterator<FieldDtoInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * @param FieldInterface[]|string[] $fields
     *
     * @return FieldDtoInterface[]
     */
    private function processFields(iterable $fields): array
    {
        $dtos = [];

        // for DX reasons, fields can be configured as a FieldInterface object and
        // as a simple string with the name of the Doctrine property
        /** @var FieldInterface|string $field */
        foreach ($fields as $field) {
            if (\is_string($field)) {
                $field = Field::new($field);
            }

            $dto = $field->getAsDto();
            if (null === $dto->getFieldFqcn()) {
                $dto->setFieldFqcn($field::class);
            }
            $dtos[$dto->getUniqueId()] = $dto;
        }

        return $dtos;
    }
}
