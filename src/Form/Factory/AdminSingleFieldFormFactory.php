<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

class AdminSingleFieldFormFactory
{
    public const FORM_NAME = '_edit_in_place';

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    public function createBuilder(FieldDto $fieldDto): FormBuilderInterface
    {
        $builder = $this->formFactory->createNamedBuilder(self::FORM_NAME);

        $builder->add(
            'newValue',
            $fieldDto->getFormType(),
            array_merge($fieldDto->getFormTypeOptions(), [
                'csrf_protection' => false,
                'data' => $fieldDto->getValue(),
                'label' => false,
            ])
        );
        $builder->add(
            'fieldName',
            HiddenType::class,
            [
                'data' => $fieldDto->getProperty(),
                'label' => false,
            ],
        );

        return $builder;
    }
}
