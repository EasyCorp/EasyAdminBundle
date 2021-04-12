<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\NullFilterType;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NullFilter implements FilterInterface
{
    use FilterTrait;

    private const CHOICE_VALUE_NULL = 'null';
    private const CHOICE_VALUE_NOT_NULL = 'not_null';

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(NullFilterType::class);
    }

    public function setChoiceLabels(string $nullChoiceLabel, string $notNullChoiceLabel): self
    {
        $this->dto->setFormTypeOption('choices', [
            $nullChoiceLabel => self::CHOICE_VALUE_NULL,
            $notNullChoiceLabel => self::CHOICE_VALUE_NOT_NULL,
        ]);

        return $this;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $comparison = self::CHOICE_VALUE_NULL === $filterDataDto->getValue() ? 'IS' : 'IS NOT';
        $queryBuilder
            ->andWhere(sprintf('%s.%s %s NULL', $filterDataDto->getEntityAlias(), $filterDataDto->getProperty(), $comparison));
    }
}
