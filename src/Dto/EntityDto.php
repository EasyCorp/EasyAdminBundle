<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @template TEntity of object
 */
final class EntityDto implements \Stringable
{
    private bool $isAccessible = true;
    private mixed $primaryKeyValue = null;
    private ?FieldCollection $fields = null;
    private ?ActionCollection $actions = null;
    private ?string $defaultActionUrl = null;

    /**
     * @param class-string<TEntity>  $fqcn
     * @param ClassMetadata<TEntity> $metadata
     * @param TEntity|null           $entityInstance
     */
    public function __construct(
        private readonly string $fqcn,
        private readonly ClassMetadata $metadata,
        private readonly string|Expression|null $permission = null,
        private ?object $entityInstance = null,
    ) {
    }

    public function __toString(): string
    {
        if (null === $this->entityInstance) {
            return '';
        }

        if ($this->entityInstance instanceof \Stringable) {
            return (string) $this->entityInstance;
        }

        return sprintf('%s #%s', $this->getName(), substr($this->getPrimaryKeyValueAsString(), 0, 16));
    }

    /**
     * @return class-string<TEntity>
     */
    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getName(): string
    {
        return basename(str_replace('\\', '/', $this->fqcn));
    }

    /**
     * @phpstan-return TEntity|null
     */
    public function getInstance(): ?object
    {
        return $this->entityInstance;
    }

    public function getPrimaryKeyValue(): mixed
    {
        if (null === $this->entityInstance) {
            return null;
        }

        if (null !== $this->primaryKeyValue) {
            return $this->primaryKeyValue;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        try {
            $primaryKeyValue = $propertyAccessor->getValue($this->entityInstance, $this->metadata->getSingleIdentifierFieldName());
        } catch (UninitializedPropertyException $exception) {
            $primaryKeyValue = null;
        }

        return $this->primaryKeyValue = $primaryKeyValue;
    }

    public function getPrimaryKeyValueAsString(): string
    {
        return (string) $this->getPrimaryKeyValue();
    }

    public function getPermission(): string|Expression|null
    {
        return $this->permission;
    }

    public function isAccessible(): bool
    {
        return $this->isAccessible;
    }

    public function markAsInaccessible(): void
    {
        $this->isAccessible = false;
        $this->entityInstance = null;
        $this->fields = null;
    }

    public function getFields(): ?FieldCollection
    {
        return $this->fields;
    }

    public function setFields(FieldCollection $fields): void
    {
        $this->fields = $fields;
    }

    public function setActions(ActionCollection $actions): void
    {
        $this->actions = $actions;
    }

    public function getActions(): ActionCollection
    {
        return $this->actions;
    }

    public function getDefaultActionUrl(): ?string
    {
        return $this->defaultActionUrl;
    }

    public function setDefaultActionUrl(?string $url): void
    {
        $this->defaultActionUrl = $url;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->metadata;
    }

    /**
     * @param TEntity|null $newEntityInstance
     */
    public function setInstance(?object $newEntityInstance): void
    {
        if (null !== $this->entityInstance && null !== $newEntityInstance && !$newEntityInstance instanceof $this->fqcn) {
            throw new \InvalidArgumentException(sprintf('The new entity instance must be of the same type as the previous instance (original instance: "%s", new instance: "%s").', $this->fqcn, $newEntityInstance::class));
        }

        $this->entityInstance = $newEntityInstance;
        $this->primaryKeyValue = null;
    }

    /**
     * @param TEntity $newEntityInstance
     */
    public function newWithInstance(object $newEntityInstance): self
    {
        if (null !== $this->entityInstance && !$newEntityInstance instanceof $this->fqcn) {
            throw new \InvalidArgumentException(sprintf('The new entity instance must be of the same type as the previous instance (original instance: "%s", new instance: "%s").', $this->fqcn, $newEntityInstance::class));
        }

        return new self($this->fqcn, $this->metadata, $this->permission, $newEntityInstance);
    }
}
