<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use Symfony\Component\Form\FormInterface;
use function Symfony\Component\String\u;

final class TextFilter
{
    private $formType;
    private $property;
    private $label;

    private function __construct()
    {
    }

    public function __toString(): string
    {
        return $this->property;
    }

    public static function new(string $propertyName, $label = null): self
    {
        $filter = new self();
        $filter->formType = TextFilterType::class;
        $filter->property = $propertyName;
        $filter->label = $label ?? u($propertyName)->title(true)->toString();

        return $filter;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getLabel()
    {
        return $this->label;
    }



    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto)
    {
        $queryBuilder->andWhere(sprintf('%s.%s = :%s', $filterDataDto->getEntityAlias(), $filterDataDto->getProperty(), $filterDataDto->getParameterName()))
            ->setParameter($filterDataDto->getParameterName(), $filterDataDto->getValue());
    }
}
