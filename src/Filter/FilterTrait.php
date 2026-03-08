<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait FilterTrait
{
    private FilterDto $dto;

    private function __construct()
    {
        $dto = new FilterDto();
        $dto->setApplyCallable(fn (QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto) => $this->apply($queryBuilder, $filterDataDto, $fieldDto, $entityDto));

        $this->dto = $dto;
    }

    public function __toString(): string
    {
        return $this->dto->getProperty();
    }

    public function setFilterFqcn(string $fqcn): self
    {
        $this->dto->setFqcn($fqcn);

        return $this;
    }

    public function setProperty(string $propertyName): self
    {
        $this->dto->setProperty($propertyName);

        return $this;
    }

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public function setLabel(/* TranslatableInterface|string|false|null */ $label): self
    {
        if (!\is_string($label) && !$label instanceof TranslatableInterface && false !== $label && null !== $label) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Argument "%s" for "%s" must be one of these types: %s. Passing type "%s" will cause an error in 5.0.0.',
                '$label',
                __METHOD__,
                '"string" or "TranslatableInterface" or "false" or "null"',
                \gettype($label)
            );
        }

        $this->dto->setLabel($label);

        return $this;
    }

    public function setFormType(string $formType): self
    {
        $this->dto->setFormType($formType);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setFormTypeOptions(array $options): self
    {
        $this->dto->setFormTypeOptions($options);

        return $this;
    }

    public function setFormTypeOption(string $optionName, mixed $optionValue): self
    {
        $this->dto->setFormTypeOption($optionName, $optionValue);

        return $this;
    }

    public function getAsDto(): FilterDto
    {
        return $this->dto;
    }
}
