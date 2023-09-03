<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterDtoInterface
{
    public function getFqcn(): ?string;

    public function setFqcn(string $fqcn): void;

    public function getFormType(): ?string;

    public function getFormTypeOptions(): array;

    public function getFormTypeOption(string $optionName);

    public function setFormTypeOptions(array $formTypeOptions): void;

    public function setFormTypeOption(string $optionName, mixed $optionValue): void;

    public function setFormTypeOptionIfNotSet(string $optionName, mixed $optionValue): void;

    public function setFormType(string $formType): void;

    public function getProperty(): string;

    public function setProperty(string $propertyName): void;

    /**
     * @return TranslatableInterface|string|false|null
     */
    public function getLabel();

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public function setLabel($label): void;

    public function setApplyCallable(callable $callable): void;

    public function apply(
        QueryBuilder $queryBuilder,
        FilterDataDtoInterface $filterDataDto,
        ?FieldDtoInterface $fieldDto,
        EntityDtoInterface $entityDto
    ): void;
}
