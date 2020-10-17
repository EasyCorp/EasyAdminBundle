<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EaFormTabSubscriber;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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
    private $doctrineOrmTypeGuesser;

    public function __construct(DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser)
    {
        $this->doctrineOrmTypeGuesser = $doctrineOrmTypeGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityDto $entityDto */
        $entityDto = $options['entityDto'];

        foreach ($entityDto->getFields() as $fieldDto) {
            if ($fieldDto->isDecorator()) {
                continue;
            }

            $formFieldOptions = $fieldDto->getFormTypeOptions();

            // the names of embedded Doctrine entities contain dots, which are not allowed
            // in HTML element names. In those cases, fix the name but also update the
            // 'property_path' option to keep the original field name
            if (false !== strpos($fieldDto->getProperty(), '.')) {
                $formFieldOptions['property_path'] = $fieldDto->getProperty();
                $name = str_replace('.', '_', $fieldDto->getProperty());
            } else {
                $name = $fieldDto->getProperty();
            }

            if (null === $formFieldType = $fieldDto->getFormType()) {
                $guessType = $this->doctrineOrmTypeGuesser->guessType($entityDto->getFqcn(), $fieldDto->getProperty());
                $formFieldType = $guessType->getType();
                $formFieldOptions = array_merge($guessType->getOptions(), $formFieldOptions);
            }

            $formField = $builder->getFormFactory()->createNamedBuilder($name, $formFieldType, null, $formFieldOptions);
            $formField->setAttribute('ea_entity', $entityDto);
            $formField->setAttribute('ea_field', $fieldDto);

            $builder->add($formField);
        }

        $builder->addEventSubscriber(new EaFormTabSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // some properties and field types require CSS/JS assets to work properly
        // get all field assets and pass them as a form variable
        $allFormFieldAssets = new AssetsDto();

        /** @var FieldDto $fieldDto */
        foreach ($options['entityDto']->getFields() as $fieldDto) {
            $allFormFieldAssets = $allFormFieldAssets->mergeWith($fieldDto->getAssets());
        }

        $view->vars['ea_crud_form'] = [
            'assets' => $allFormFieldAssets,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'allow_extra_fields' => true,
                'data_class' => static function (Options $options, $dataClass) {
                    return $dataClass ?? $options['entityDto']->getFqcn();
                },
            ])
            ->setDefined(['entityDto'])
            ->setRequired(['entityDto']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ea_crud';
    }
}
