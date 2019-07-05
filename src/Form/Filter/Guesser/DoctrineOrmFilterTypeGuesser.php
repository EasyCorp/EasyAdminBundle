<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Guesser;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\NumericFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
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
class DoctrineOrmFilterTypeGuesser extends DoctrineOrmTypeGuesser
{
    private static $defaultOptions = [
        'translation_domain' => 'EasyAdminBundle',
    ];

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        if (!$doctrineEntityMetadata = $this->getMetadata($class)) {
            return null;
        }

        /** @var ClassMetadataInfo $metadata */
        [$metadata, $name] = $doctrineEntityMetadata;

        if ($metadata->hasAssociation($property)) {
            $multiple = $metadata->isCollectionValuedAssociation($property);
            $mapping = $metadata->getAssociationMapping($property);
            $options = ['value_type_options' => [
                'em' => $name,
                'class' => $mapping['targetEntity'],
                'multiple' => $multiple,
                'attr' => ['data-widget' => 'select2'],
            ]];

            if ($metadata->isSingleValuedAssociation($property)) {
                // don't show the 'empty value' placeholder when all join columns are required,
                // because an empty filter value would always returns no result
                $numberOfRequiredJoinColumns = \count(\array_filter($mapping['joinColumns'], function (array $joinColumnMapping): bool {
                    return false === ($joinColumnMapping['nullable'] ?? false);
                }));
                $someJoinColumnsAreNullable = \count($mapping['joinColumns']) !== $numberOfRequiredJoinColumns;
                if ($someJoinColumnsAreNullable) {
                    $options['value_type_options']['placeholder'] = 'label.form.empty_value';
                }
            }

            return new TypeGuess(EntityFilterType::class, self::$defaultOptions + $options, Guess::HIGH_CONFIDENCE);
        }

        switch ($metadata->getTypeOfField($property)) {
            case Type::JSON_ARRAY:
            case Type::SIMPLE_ARRAY:
            case Type::TARRAY:
                return new TypeGuess(ArrayFilterType::class, self::$defaultOptions + [], Guess::MEDIUM_CONFIDENCE);

            case Type::JSON:
                return new TypeGuess(TextFilterType::class, self::$defaultOptions + ['value_type' => TextareaType::class], Guess::MEDIUM_CONFIDENCE);

            case Type::BOOLEAN:
                return new TypeGuess(BooleanFilterType::class, self::$defaultOptions + [], Guess::HIGH_CONFIDENCE);

            case Type::DATETIME:
            case Type::DATETIMETZ:
                return new TypeGuess(DateTimeFilterType::class, self::$defaultOptions + [], Guess::HIGH_CONFIDENCE);

            case Type::DATETIME_IMMUTABLE:
            case Type::DATETIMETZ_IMMUTABLE:
                return new TypeGuess(DateTimeFilterType::class, self::$defaultOptions + ['value_type_options' => ['input' => 'datetime_immutable']], Guess::HIGH_CONFIDENCE);

            case Type::DATEINTERVAL:
                return new TypeGuess(ComparisonFilterType::class, self::$defaultOptions + ['value_type' => DateIntervalType::class, 'comparison_type_options' => ['type' => 'datetime']], Guess::HIGH_CONFIDENCE);

            case Type::DATE:
                return new TypeGuess(DateTimeFilterType::class, self::$defaultOptions + ['value_type' => DateType::class], Guess::HIGH_CONFIDENCE);

            case Type::DATE_IMMUTABLE:
                return new TypeGuess(DateTimeFilterType::class, self::$defaultOptions + ['value_type' => DateType::class, 'value_type_options' => ['input' => 'datetime_immutable']], Guess::HIGH_CONFIDENCE);

            case Type::TIME:
                return new TypeGuess(DateTimeFilterType::class, self::$defaultOptions + ['value_type' => TimeType::class], Guess::HIGH_CONFIDENCE);

            case Type::TIME_IMMUTABLE:
                return new TypeGuess(DateTimeFilterType::class, self::$defaultOptions + ['value_type' => TimeType::class, 'value_type_options' => ['input' => 'datetime_immutable']], Guess::HIGH_CONFIDENCE);

            case Type::DECIMAL:
                return new TypeGuess(NumericFilterType::class, self::$defaultOptions + ['value_type_options' => ['input' => 'string']], Guess::MEDIUM_CONFIDENCE);

            case Type::FLOAT:
                return new TypeGuess(NumericFilterType::class, self::$defaultOptions, Guess::MEDIUM_CONFIDENCE);

            case Type::BIGINT:
            case Type::INTEGER:
            case Type::SMALLINT:
                return new TypeGuess(NumericFilterType::class, self::$defaultOptions + ['value_type' => IntegerType::class], Guess::MEDIUM_CONFIDENCE);

            case Type::GUID:
            case Type::STRING:
                return new TypeGuess(TextFilterType::class, self::$defaultOptions + [], Guess::MEDIUM_CONFIDENCE);

            case Type::BLOB:
            case Type::OBJECT:
            case Type::TEXT:
                return new TypeGuess(TextFilterType::class, self::$defaultOptions + ['value_type' => TextareaType::class], Guess::MEDIUM_CONFIDENCE);
        }

        return null;
    }
}
