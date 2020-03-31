<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Guesser;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FilterTypeGuesser
{
    private static $defaultOptions = [
        'translation_domain' => 'EasyAdminBundle',
    ];

    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function guessType(EntityDto $entityDto, string $propertyName): ?Filter
    {
        $metadata = $entityDto->getPropertyMetadata($propertyName);

        if (empty($metadata)) {
            return new TypeGuess(TextFilter::class, [], Guess::MEDIUM_CONFIDENCE);
        }

        /** @var ClassMetadataInfo $metadata */
        [$metadata, $entityManagerName] = $doctrineEntityMetadata;

        if ($metadata->hasAssociation($propertyName)) {
            $multiple = $metadata->isCollectionValuedAssociation($propertyName);
            $mapping = $metadata->getAssociationMapping($propertyName);
            $options = [
                'em' => $entityManagerName,
                'class' => $mapping['targetEntity'],
                'multiple' => $multiple,
                'attr' => ['data-widget' => 'select2'],
            ];

            if ($metadata->isSingleValuedAssociation($propertyName)) {
                // don't show the 'empty value' placeholder when all join columns are required,
                // because an empty filter value would always returns no result
                $numberOfRequiredJoinColumns = \count(array_filter($mapping['joinColumns'], function (array $joinColumnMapping): bool {
                    return false === ($joinColumnMapping['nullable'] ?? false);
                }));
                $someJoinColumnsAreNullable = \count($mapping['joinColumns']) !== $numberOfRequiredJoinColumns;
                if ($someJoinColumnsAreNullable) {
                    $options['value_type_options']['placeholder'] = 'label.form.empty_value';
                }
            }

            return new TypeGuess(EntityFilter::class, self::$defaultOptions + $options, Guess::HIGH_CONFIDENCE);
        }

        switch ($metadata->getTypeOfField($propertyName)) {
            case Type::JSON_ARRAY:
            case Type::SIMPLE_ARRAY:
            case Type::TARRAY:
                return new TypeGuess(ArrayFilter::class, self::$defaultOptions + [], Guess::MEDIUM_CONFIDENCE);

            case Type::JSON:
                return new TypeGuess(TextFilter::class, self::$defaultOptions + ['value_type' => TextareaType::class], Guess::MEDIUM_CONFIDENCE);

            case Type::BOOLEAN:
                return new TypeGuess(BooleanFilter::class, self::$defaultOptions + [], Guess::HIGH_CONFIDENCE);

            case Type::DATETIME:
            case Type::DATETIMETZ:
                return new TypeGuess(DateTimeFilter::class, self::$defaultOptions + [], Guess::HIGH_CONFIDENCE);

            case Type::DATETIME_IMMUTABLE:
            case Type::DATETIMETZ_IMMUTABLE:
                return new TypeGuess(DateTimeFilter::class, self::$defaultOptions + ['value_type_options' => ['input' => 'datetime_immutable']], Guess::HIGH_CONFIDENCE);

            case Type::DATEINTERVAL:
                return new TypeGuess(ComparisonFilterType::class, self::$defaultOptions + ['value_type' => DateIntervalType::class, 'comparison_type_options' => ['type' => 'datetime']], Guess::HIGH_CONFIDENCE);

            case Type::DATE:
                return new TypeGuess(DateTimeFilter::class, self::$defaultOptions + ['value_type' => DateType::class], Guess::HIGH_CONFIDENCE);

            case Type::DATE_IMMUTABLE:
                return new TypeGuess(DateTimeFilter::class, self::$defaultOptions + ['value_type' => DateType::class, 'value_type_options' => ['input' => 'datetime_immutable']], Guess::HIGH_CONFIDENCE);

            case Type::TIME:
                return new TypeGuess(DateTimeFilter::class, self::$defaultOptions + ['value_type' => TimeType::class], Guess::HIGH_CONFIDENCE);

            case Type::TIME_IMMUTABLE:
                return new TypeGuess(DateTimeFilter::class, self::$defaultOptions + ['value_type' => TimeType::class, 'value_type_options' => ['input' => 'datetime_immutable']], Guess::HIGH_CONFIDENCE);

            case Type::DECIMAL:
                return new TypeGuess(NumericFilter::class, self::$defaultOptions + ['value_type_options' => ['input' => 'string']], Guess::MEDIUM_CONFIDENCE);

            case Type::FLOAT:
                return new TypeGuess(NumericFilter::class, self::$defaultOptions, Guess::MEDIUM_CONFIDENCE);

            case Type::BIGINT:
            case Type::INTEGER:
            case Type::SMALLINT:
                return new TypeGuess(NumericFilter::class, self::$defaultOptions + ['value_type' => IntegerType::class], Guess::MEDIUM_CONFIDENCE);

            case Type::GUID:
            case Type::STRING:
                return new TypeGuess(TextFilter::class, self::$defaultOptions + [], Guess::MEDIUM_CONFIDENCE);

            case Type::BLOB:
            case Type::OBJECT:
            case Type::TEXT:
                return new TypeGuess(TextFilter::class, self::$defaultOptions + ['value_type' => TextareaType::class], Guess::MEDIUM_CONFIDENCE);
        }

        return null;
    }

    private function getMetadata(string $entityFqcn): ?array
    {
        foreach ($this->doctrine->getManagers() as $name => $entityManager) {
            try {
                return [$entityManager->getClassMetadata($entityFqcn), $name];
            } catch (\Exception $e) {
            }
        }

        return null;
    }
}
