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
    private ?string $propertyName = null;
    private $label;
    private $applyCallable;

    public function __construct()
    {
        $this->formTypeOptions = KeyValueStore::new();
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

    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions->all();
    }

    public function getFormTypeOption(string $optionName)
    {
        return $this->formTypeOptions->get($optionName);
    }

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

    /**
     * @return TranslatableInterface|string|false|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public function setLabel($label): void
    {
        if (!\is_string($label) && !$label instanceof TranslatableInterface && false !== $label && null !== $label) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.0.5',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                '"string", "false" or "null"',
                \gettype($label)
            );
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
}
