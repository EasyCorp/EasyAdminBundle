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
    /** @var FieldDto[] */
    private array $fields;
    /** @var FieldDto[] */
    private array $tabs;

    public function __construct(array $fields = [], array $tabs = [])
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.8.0',
            'The "%s" class is deprecated and it will be removed in 5.0.0 because a DTO is no longer used to handle the form layout. Check the new "%s" class.',
            __CLASS__, FormLayoutFactory::class
        );

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
