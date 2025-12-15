<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityFilter implements FilterInterface
{
    use FilterTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
    }

    public function canSelectMultiple(bool $selectMultiple = true): self
    {
        $this->dto->setFormTypeOption('value_type_options.multiple', $selectMultiple);

        return $this;
    }

    public function autocomplete(bool $autocomplete = true): self
    {
        $this->dto->setFormTypeOption('autocomplete', $autocomplete);

        return $this;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $property = $filterDataDto->getProperty();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $value = $filterDataDto->getValue();
        $isMultiple = (bool) $filterDataDto->getFormTypeOption('value_type_options.multiple');

        $aliasToUse = $alias;
        $propertyToUse = $property;

        if (str_contains($property, '.')) {
            [$joinAlias, $propertyPath] = $this->createJoinForAssociationFilter($queryBuilder, $alias, $property, $parameterName);
            $aliasToUse = $joinAlias;
            $propertyToUse = $propertyPath;
        }

        $classMetadata = $entityDto->getClassMetadata();
        if (str_contains($property, '.')) {
            $em = $queryBuilder->getEntityManager();
            $metadata = $classMetadata;
            $parts = explode('.', $property);
            $lastProperty = array_pop($parts);
            foreach ($parts as $association) {
                if (!$metadata->hasAssociation($association)) {
                    break;
                }
                $targetClass = $metadata->getAssociationTargetClass($association);
                $metadata = $em->getClassMetadata($targetClass);
            }
            $classMetadata = $metadata;
            $propertyToUse = $lastProperty;
        }

        if ($classMetadata->hasAssociation($propertyToUse) && $classMetadata->isCollectionValuedAssociation($propertyToUse)) {
            // the 'ea_' prefix is needed to avoid errors when using reserved words as assocAlias ('order', 'group', etc.)
            // see https://github.com/EasyCorp/EasyAdminBundle/pull/4344
            $assocAlias = 'ea_'.$filterDataDto->getParameterName();
            $queryBuilder->leftJoin(sprintf('%s.%s', $aliasToUse, $propertyToUse), $assocAlias);

            if (0 === \count($value)) {
                $queryBuilder->andWhere(sprintf('%s %s', $assocAlias, $comparison));
            } else {
                $orX = new Orx();
                $orX->add(sprintf('%s %s (:%s)', $assocAlias, $comparison, $parameterName));
                if ('NOT IN' === $comparison) {
                    $orX->add(sprintf('%s IS NULL', $assocAlias));
                }
                $queryBuilder->andWhere($orX)
                    ->setParameter($parameterName, $this->processParameterValue($queryBuilder, $value));
            }
        } elseif (null === $value || ($isMultiple && 0 === \count($value))) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $aliasToUse, $propertyToUse, $comparison));
        } else {
            $orX = new Orx();
            $orX->add(sprintf('%s.%s %s (:%s)', $aliasToUse, $propertyToUse, $comparison, $parameterName));
            if (ComparisonType::NEQ === $comparison) {
                $orX->add(sprintf('%s.%s IS NULL', $aliasToUse, $propertyToUse));
            }
            $queryBuilder->andWhere($orX)
                ->setParameter($parameterName, $this->processParameterValue($queryBuilder, $value));
        }
    }

    private function processParameterValue(QueryBuilder $queryBuilder, mixed $parameterValue): mixed
    {
        if (!$parameterValue instanceof ArrayCollection) {
            return $this->processSingleParameterValue($queryBuilder, $parameterValue);
        }

        return $parameterValue->map(fn ($element) => $this->processSingleParameterValue($queryBuilder, $element));
    }

    /**
     * If the parameter value is a bound entity or a collection of bound entities
     * and the PHP value of the entity's identifier is either of type
     * "Symfony\Component\Uid\Uuid" or "Symfony\Component\Uid\Ulid" defined in
     * symfony/uid then the parameter value is converted from the entity to the
     * database value of its primary key.
     *
     * Otherwise, the parameter value is not processed.
     *
     * For example, if the used platform is MySQL:
     *
     *      App\Entity\Category {#1040 ▼
     *          -id: Symfony\Component\Uid\UuidV6 {#1046 ▼
     *              #uid: "1ec4d51f-c746-6f60-b698-634384c1b64c"
     *          }
     *          -title: "cat 2"
     *      }
     *
     *  gets processed to a binary value:
     *
     *      b"\x1EÄÕ\x1FÇFo`¶˜cC„Á¶L"
     */
    private function processSingleParameterValue(QueryBuilder $queryBuilder, mixed $parameterValue): mixed
    {
        $entityManager = $queryBuilder->getEntityManager();

        try {
            $classMetadata = $entityManager->getClassMetadata($parameterValue::class);
        } catch (\Throwable) {
            // only reached if $parameterValue does not contain an object of a managed
            // entity, return as we only need to process bound entities
            return $parameterValue;
        }

        try {
            $identifierType = $classMetadata->getTypeOfField($classMetadata->getSingleIdentifierFieldName());
        } catch (MappingException) {
            throw new \RuntimeException(sprintf('The EntityFilter does not support entities with a composite primary key or entities without an identifier. Please check your entity "%s".', $parameterValue::class));
        }

        $identifierValue = $entityManager->getUnitOfWork()->getSingleIdentifierValue($parameterValue);

        if ($identifierValue instanceof Uuid || $identifierValue instanceof Ulid) {
            try {
                return Type::getType($identifierType)->convertToDatabaseValue(
                    $identifierValue,
                    $entityManager->getConnection()->getDatabasePlatform()
                );
            } catch (\Throwable) {
                // if the conversion fails we cannot process the uid parameter value
            }
        }

        return $parameterValue;
    }
}
