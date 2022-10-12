<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
final class FieldLayoutDto
{
    /** @var FieldDto[] */
    private array $fields;
    /** @var FieldDto[] */
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
