<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\FormLayoutSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnOpenType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormFieldsetCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormFieldsetOpenType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormRowType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneOpenType;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form type that deals with some of the logic used to render the
 * forms used to create and edit EasyAdmin entities.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class CrudFormType extends AbstractType
{
    public function __construct(private readonly DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var EntityDto $entityDto */
        $entityDto = $options['entityDto'];

        foreach ($this->createFormFields($builder->getFormFactory(), $entityDto) as $formField) {
            $builder->add($formField);
        }

        // a CRUD form embedded in another nested CRUD form (e.g. a collection entry) can't know if it creates
        // or edits an entity until it receives its data, so its fields are configured for both cases: it
        // shows the fields of 'entityDto' by default and replaces them by the ones of 'editEntityDto' when editing
        if (null !== $editEntityDto = $options['editEntityDto']) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($editEntityDto): void {
                if (null === $event->getData()) {
                    return;
                }

                $form = $event->getForm();
                foreach ($form->all() as $name => $child) {
                    if (null !== $child->getConfig()->getAttribute('ea_entity')) {
                        $form->remove((string) $name);
                    }
                }

                foreach ($this->createFormFields($form->getConfig()->getFormFactory(), $editEntityDto) as $formField) {
                    $form->add($formField->setAutoInitialize(false)->getForm());
                }
            });
        }

        $builder->addEventSubscriber(new FormLayoutSubscriber());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'allow_extra_fields' => true,
                // the EntityDto with the fields to use instead of the ones of 'entityDto' when the form receives an entity to edit
                'editEntityDto' => null,
                'data_class' => static fn (Options $options, $dataClass) => $dataClass ?? $options['entityDto']->getFqcn(),
            ])
            ->setDefined(['entityDto'])
            ->setRequired(['entityDto'])
            ->setAllowedTypes('editEntityDto', ['null', EntityDto::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'ea_crud';
    }

    /**
     * @return list<FormBuilderInterface>
     */
    private function createFormFields(FormFactoryInterface $formFactory, EntityDto $entityDto): array
    {
        $formFields = [];

        /** @var FieldDto $fieldDto */
        foreach ($entityDto->getFields() as $fieldDto) {
            $formFieldOptions = $fieldDto->getFormTypeOptions();

            // the names of embedded Doctrine entities contain dots, which are not allowed
            // in HTML element names. In those cases, fix the name but also update the
            // 'property_path' option to keep the original field name
            if (str_contains($fieldDto->getProperty(), '.')) {
                $formFieldOptions['property_path'] = $fieldDto->getProperty();
                $name = str_replace(['.', '[', ']', '?'], '_', $fieldDto->getProperty());
            } else {
                $name = $fieldDto->getProperty();
            }

            if (null === $formFieldType = $fieldDto->getFormType()) {
                $guessType = $this->doctrineOrmTypeGuesser->guessType($entityDto->getFqcn(), $fieldDto->getProperty());
                $formFieldType = $guessType->getType();
                $formFieldOptions = array_merge($guessType->getOptions(), $formFieldOptions);
            }

            $name = $this->isTypeFormField($formFieldType) ? $fieldDto->getPropertyNameWithSuffix() : $name;

            $formField = $formFactory->createNamedBuilder($name, $formFieldType, null, $formFieldOptions);
            $formField->setAttribute('ea_entity', $entityDto);
            $formField->setAttribute('ea_field', $fieldDto);

            $formFields[] = $formField;
        }

        return $formFields;
    }

    private function isTypeFormField(?string $type): bool
    {
        if (null === $type) {
            return false;
        }

        return \in_array($type, [
            EaFormFieldsetOpenType::class,
            EaFormFieldsetCloseType::class,

            EaFormRowType::class,

            EaFormTabPaneOpenType::class,
            EaFormTabPaneCloseType::class,

            EaFormColumnOpenType::class,
            EaFormColumnCloseType::class,
        ], true);
    }
}
