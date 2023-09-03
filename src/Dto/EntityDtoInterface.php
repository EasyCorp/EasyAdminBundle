<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStoreInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityDtoInterface
{
    public function getFqcn(): string;

    public function getName(): string;

    public function toString(): string;

    public function getInstance();

    public function getPrimaryKeyName(): ?string;

    public function getPrimaryKeyValue(): mixed;

    public function getPrimaryKeyValueAsString(): string;

    public function getPermission(): ?string;

    public function isAccessible(): bool;

    public function markAsInaccessible(): void;

    public function getFields(): ?FieldCollection;

    public function setFields(FieldCollection $fields): void;

    public function setActions(ActionCollection $actions): void;

    public function getActions(): ActionCollection;

    /**
     * Returns the names of all properties defined in the entity, no matter
     * if they are used or not in the application.
     */
    public function getAllPropertyNames(): array;

    public function getPropertyMetadata(string $propertyName): KeyValueStoreInterface;

    public function getPropertyDataType(string $propertyName);

    public function hasProperty(string $propertyName): bool;

    public function isAssociation(string $propertyName): bool;

    public function isToOneAssociation(string $propertyName): bool;

    public function isToManyAssociation(string $propertyName): bool;

    public function isEmbeddedClassProperty(string $propertyName): bool;

    public function setInstance(?object $newEntityInstance): void;

    public function newWithInstance($newEntityInstance): EntityDtoInterface;
}
