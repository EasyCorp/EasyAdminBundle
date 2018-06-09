<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Guesser;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class MissingDoctrineOrmTypeGuesser extends DoctrineOrmTypeGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        if (null !== $metadataAndName = $this->getMetadata($class)) {
            /** @var ClassMetadataInfo $metadata */
            $metadata = $metadataAndName[0];

            switch ($metadata->getTypeOfField($property)) {
                case 'datetime_immutable': // available since Doctrine 2.6
                    return new TypeGuess(DateTimeType::class, [], Guess::HIGH_CONFIDENCE);
                case 'date_immutable': // available since Doctrine 2.6
                    return new TypeGuess(DateType::class, [], Guess::HIGH_CONFIDENCE);
                case 'time_immutable': // available since Doctrine 2.6
                    return new TypeGuess(TimeType::class, [], Guess::HIGH_CONFIDENCE);
                case Type::SIMPLE_ARRAY:
                case Type::JSON_ARRAY:
                    return new TypeGuess(CollectionType::class, [], Guess::MEDIUM_CONFIDENCE);
                case 'json': // available since Doctrine 2.6.2
                    return new TypeGuess(TextareaType::class, [], Guess::MEDIUM_CONFIDENCE);
                case Type::OBJECT:
                case Type::BLOB:
                    return new TypeGuess(TextareaType::class, [], Guess::MEDIUM_CONFIDENCE);
                case Type::GUID:
                    return new TypeGuess(TextType::class, [], Guess::MEDIUM_CONFIDENCE);
            }
        }

        return parent::guessType($class, $property);
    }
}
