<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
interface FieldLayoutDtoInterface
{
    public function hasTabs(): bool;

    public function getTabs(): array;

    public function getFields(): array;

    public function getFieldsInTab(string $tabUniqueId): array;
}
