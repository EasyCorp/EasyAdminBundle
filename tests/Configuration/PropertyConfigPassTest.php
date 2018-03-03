<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\DependencyInjection\Compiler;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\PropertyConfigPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class PropertyConfigPassTest extends TestCase
{
    public function testUnknownGuessedFormTypeOptionsAreRemoved()
    {
        $backendConfig = ['entities' => [
            'TestEntity' => [
                'class' => 'AppBundle\Entity\TestEntity',
                'properties' => [
                    'relations' => [
                        'type' => 'association',
                    ],
                ],
                'edit' => [
                    'fields' => [
                        'relations' => [
                            'property' => 'relations',
                            'type' => 'collection',
                            'type_options' => [
                                'entry_type' => 'AppBundle\Form\Type\EntityRelationType',
                            ],
                        ],
                    ],
                ],
                'new' => ['fields' => []],
                'list' => ['fields' => []],
                'search' => ['fields' => []],
                'show' => ['fields' => []],
            ],
        ]];

        $configPass = new PropertyConfigPass($this->getFormRegistry());
        $backendConfig = $configPass->process($backendConfig);

        $relationsFormConfig = $backendConfig['entities']['TestEntity']['edit']['fields']['relations'];

        // Assert that unknown form options from guessed type (EntityType) are removed
        $this->assertFalse(isset($relationsFormConfig['type_options']['em']));
        $this->assertFalse(isset($relationsFormConfig['type_options']['class']));
        $this->assertFalse(isset($relationsFormConfig['type_options']['multiple']));
        // Assert that option from custom form type is still set.
        $this->assertSame(
            $relationsFormConfig['type_options']['entry_type'],
            'AppBundle\Form\Type\EntityRelationType'
        );
    }

    public function testSameFormTypeOptionsMustKeepGuessedFormOptions()
    {
        $backendConfig = ['entities' => [
            'TestEntity' => [
                'class' => 'AppBundle\Entity\TestEntity',
                'properties' => [
                    'relations' => [
                        'type' => 'association',
                    ],
                ],
                'edit' => [
                    'fields' => [
                        'relations' => [
                            'property' => 'relations',
                            'type' => 'entity',
                            'type_options' => [
                                'expanded' => true,
                                'multiple' => false,
                            ],
                        ],
                    ],
                ],
                'new' => ['fields' => []],
                'list' => ['fields' => []],
                'search' => ['fields' => []],
                'show' => ['fields' => []],
            ],
        ]];

        $configPass = new PropertyConfigPass($this->getFormRegistry());
        $backendConfig = $configPass->process($backendConfig);

        $relationsFormConfig = $backendConfig['entities']['TestEntity']['edit']['fields']['relations'];

        // Assert that option from custom form type is still set.
        $this->assertSame(
            $relationsFormConfig['type_options'],
            [
                'em' => 'default',
                'class' => 'AppBundle\Form\Type\EntityRelationType',
                'multiple' => false,
                'expanded' => true,
            ]
        );
    }

    public function testUndefinedFormTypeKeepsDefinedTypeOptions()
    {
        $backendConfig = ['entities' => [
            'TestEntity' => [
                'class' => 'AppBundle\Entity\TestEntity',
                'properties' => [
                    'relations' => [
                        'type' => 'association',
                    ],
                ],
                'edit' => [
                    'fields' => [
                        'relations' => [
                            'property' => 'relations',
                            'type_options' => [
                                'expanded' => true,
                                'multiple' => false,
                            ],
                        ],
                    ],
                ],
                'new' => ['fields' => []],
                'list' => ['fields' => []],
                'search' => ['fields' => []],
                'show' => ['fields' => []],
            ],
        ]];

        $configPass = new PropertyConfigPass($this->getFormRegistry());
        $backendConfig = $configPass->process($backendConfig);

        $relationsFormConfig = $backendConfig['entities']['TestEntity']['edit']['fields']['relations'];

        // Assert that option from custom form type is still set.
        $this->assertSame(
            $relationsFormConfig['type_options'],
            [
                'em' => 'default',
                'class' => 'AppBundle\Form\Type\EntityRelationType',
                'multiple' => false,
                'expanded' => true,
            ]
        );
    }

    private function getFormRegistry()
    {
        $doctrineTypeGuesser = $this->getMockBuilder('Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineTypeGuesser
            ->method('guessType')->willReturn(
                new TypeGuess(
                    'Symfony\Bridge\Doctrine\Form\Type\EntityType',
                    [
                        'em' => 'default',
                        'class' => 'AppBundle\Form\Type\EntityRelationType',
                        'multiple' => true,
                    ],
                    Guess::HIGH_CONFIDENCE
                )
            )
        ;

        $formRegistry = $this->getMockBuilder('Symfony\Component\Form\FormRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $formRegistry->method('getTypeGuesser')->willReturn($doctrineTypeGuesser);

        return $formRegistry;
    }
}
