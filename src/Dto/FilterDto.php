<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterDto
{
    private ?string $fqcn = null;
    private ?string $formType = null;
    private KeyValueStore $formTypeOptions;
    private KeyValueStore $customOptions;
    private ?string $propertyName = null;
    /** @var TranslatableInterface|string|false|null */
    private $label;
    /** @var callable */
    private $applyCallable;

    public function __construct()
    {
        $this->formTypeOptions = KeyValueStore::new();
        $this->customOptions = KeyValueStore::new();
    }

    public function getFqcn(): ?string
    {
        return $this->fqcn;
    }

    public function setFqcn(string $fqcn): void
    {
        $this->fqcn = $fqcn;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions->all();
    }

    public function getFormTypeOption(string $optionName): mixed
    {
        return $this->formTypeOptions->get($optionName);
    }

    /**
     * @param array<string, mixed> $formTypeOptions
     */
    public function setFormTypeOptions(array $formTypeOptions): void
    {
        $this->formTypeOptions->setAll($formTypeOptions);
    }

    public function setFormTypeOption(string $optionName, mixed $optionValue): void
    {
        $this->formTypeOptions->set($optionName, $optionValue);
    }

    public function setFormTypeOptionIfNotSet(string $optionName, mixed $optionValue): void
    {
        $this->formTypeOptions->setIfNotSet($optionName, $optionValue);
    }

    public function setFormType(string $formType): void
    {
        $this->formType = $formType;
    }

    public function getProperty(): string
    {
        return $this->propertyName;
    }

    public function setProperty(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function getLabel(): TranslatableInterface|string|bool|null
    {
        return $this->label;
    }

    public function setLabel(TranslatableInterface|string|bool|null $label): void
    {
        if (true === $label) {
            throw new \InvalidArgumentException(sprintf('The value passed to the label of the "%s" filter is not valid. When passing boolean values, you can only pass a false value (to hide the label) but you passed a true value.', $this->propertyName));
        }

        $this->label = $label;
        // needed to also display the label in the form associated to the filter
        $this->setFormTypeOption('label', $label);
    }

    public function setApplyCallable(callable $callable): void
    {
        $this->applyCallable = $callable;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        \call_user_func($this->applyCallable, $queryBuilder, $filterDataDto, $fieldDto, $entityDto);
    }

    public function getCustomOptions(): KeyValueStore
    {
        return $this->customOptions;
    }

    public function getCustomOption(string $optionName): mixed
    {
        return $this->customOptions->get($optionName);
    }

    /**
     * @param array<string, mixed> $customOptions
     */
    public function setCustomOptions(array $customOptions): void
    {
        $this->customOptions->setAll($customOptions);
    }

    public function setCustomOption(string $optionName, mixed $optionValue): void
    {
        $this->customOptions->set($optionName, $optionValue);
    }
}
