<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Factory\FormLayoutFactory;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
final class FieldLayoutDto
{
    /**
     * @param array<FieldDto>|array<string, array<FieldDto>> $fields
     * @param array<string, FieldDto>                        $tabs
     */
    public function __construct(private readonly array $fields = [], private readonly array $tabs = [])
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.8.0',
            'The "%s" class is deprecated and it will be removed in 5.0.0 because a DTO is no longer used to handle the form layout. Check the new "%s" class.',
            __CLASS__, FormLayoutFactory::class
        );
    }

    public function hasTabs(): bool
    {
        return [] !== $this->tabs;
    }

    /**
     * @return array<string, FieldDto>
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * @return array<FieldDto>|array<string, array<FieldDto>>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<FieldDto>
     */
    public function getFieldsInTab(string $tabUniqueId): array
    {
        return $this->fields[$tabUniqueId] ?? [];
    }
}
