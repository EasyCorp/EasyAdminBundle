<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class FieldLayoutDto implements FieldLayoutDtoInterface
{
    /** @var FieldDtoInterface[] */
    private array $fields;
    /** @var FieldDtoInterface[] */
    private array $tabs;

    public function __construct(array $fields = [], array $tabs = [])
    {
        $this->fields = $fields;
        $this->tabs = $tabs;
    }

    public function hasTabs(): bool
    {
        return [] !== $this->tabs;
    }

    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getFieldsInTab(string $tabUniqueId): array
    {
        return $this->fields[$tabUniqueId] ?? [];
    }
}
