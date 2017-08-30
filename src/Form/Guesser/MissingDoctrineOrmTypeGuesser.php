<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Guesser;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
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
            list($metadata) = $metadataAndName;

            switch ($metadata->getTypeOfField($property)) {
                case Type::SIMPLE_ARRAY:
                case Type::JSON_ARRAY:
                    return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\CollectionType', array(), Guess::MEDIUM_CONFIDENCE);
                case 'json': // The json type is only available since Doctrine 2.6.2
                    return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TextareaType', array(), Guess::MEDIUM_CONFIDENCE);
                case Type::OBJECT:
                case Type::BLOB:
                    return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TextareaType', array(), Guess::MEDIUM_CONFIDENCE);
                case Type::GUID:
                    return new TypeGuess('Symfony\Component\Form\Extension\Core\Type\TextType', array(), Guess::MEDIUM_CONFIDENCE);
            }
        }

        return parent::guessType($class, $property);
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Form\Guesser\MissingDoctrineOrmTypeGuesser', 'JavierEguiluz\Bundle\EasyAdminBundle\Form\Guesser\MissingDoctrineOrmTypeGuesser', false);
