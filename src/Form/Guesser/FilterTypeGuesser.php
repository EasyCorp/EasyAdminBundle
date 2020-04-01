<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Guesser;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
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

        return null;
    }
}
