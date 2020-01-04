<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use ArrayObject;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminTabSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
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
    /** @var TypeConfiguratorInterface[] */
    private $typeConfigurators;
    private $doctrineOrmTypeGuesser;

    /**
     * @param TypeConfiguratorInterface[] $typeConfigurators
     */
    public function __construct(iterable $typeConfigurators, DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser)
    {
        $this->typeConfigurators = $typeConfigurators;
        $this->doctrineOrmTypeGuesser = $doctrineOrmTypeGuesser;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityDto $entityDto */
        $entityDto = $options['entityDto'];
        // TODO: fix this
        $view = 'edit'; //$options['view'];
        $formTabs = [];
        $currentFormTab = null;
        $formGroups = [];
        $currentFormGroup = null;

        foreach ($entityDto->getProperties() as $propertyDto) {
            $formFieldOptions = $propertyDto->getFormTypeOptions();

            // the names of embedded Doctrine entities contain dots, which are not allowed
            // in HTML element names. In those cases, fix the name but also update the
            // 'property_path' option to keep the original field name
            if (false !== strpos($propertyDto->getName(), '.')) {
                $formFieldOptions['property_path'] = $propertyDto->getName();
                $name = str_replace('.', '_', $propertyDto->getName());
            } else {
                $name = $propertyDto->getName();
            }

            if (null === $formFieldType = $propertyDto->getFormType()) {
                $formFieldType = $this->doctrineOrmTypeGuesser->guessType($entityDto->getFqcn(), $propertyDto->getName())->getType();
            }

            // Configure options using the list of registered type configurators:
            foreach ($this->typeConfigurators as $configurator) {
                if ($configurator->supports($formFieldType, $formFieldOptions, $propertyDto)) {
                    $formFieldOptions = $configurator->configure($name, $formFieldOptions, $propertyDto, $builder);
                }
            }

            // if the form field is a special 'group' design element, don't add it
            // to the form. Instead, consider it the current form group (this is
            // applied to the form fields defined after it) and store its details
            // in a property to get them in form template
            if (\in_array($formFieldType, ['ea_group', EasyAdminGroupType::class])) {
                $metadata['form_tab'] = $currentFormTab ?: null;
                $currentFormGroup = $metadata['fieldName'];
                $formGroups[$currentFormGroup] = $metadata;

                continue;
            }

            // if the form field is a special 'tab' design element, don't add it
            // to the form. Instead, consider it the current form group (this is
            // applied to the form fields defined after it) and store its details
            // in a property to get them in form template
            if (\in_array($formFieldType, ['ea_tab', EasyAdminTabType::class])) {
                // The first tab should be marked as active by default
                $metadata['active'] = 0 === \count($formTabs);
                $metadata['errors'] = 0;
                $currentFormTab = $metadata['fieldName'];

                // plain arrays are not enough for tabs because they are modified in the
                // lifecycle of a form (e.g. add info about form errors). Use an ArrayObject instead.
                $formTabs[$currentFormTab] = new ArrayObject($metadata);

                continue;
            }

            // 'section' is a 'fake' form field used to create the design elements of the
            // complex form layouts: define it as unmapped and non-required
            if (0 === strpos($propertyDto->getName(), '_ea_form_design_element_')) {
                $formFieldOptions['mapped'] = false;
                $formFieldOptions['required'] = false;
            }

            //$formFieldOptions['ea_property'] = $propertyDto;

            $formField = $builder->getFormFactory()->createNamedBuilder($name, $formFieldType, null, $formFieldOptions);
            $formField->setAttribute('ea_entity', $entityDto);
            $formField->setAttribute('ea_form_group', $currentFormGroup);
            $formField->setAttribute('ea_form_tab', $currentFormTab);
            $formField->setAttribute('ea_property', $propertyDto);

            $builder->add($formField);
        }

        $builder->setAttribute('ea_form_tabs', $formTabs);
        $builder->setAttribute('ea_form_groups', $formGroups);

        if (\count($formTabs) > 0) {
            $builder->addEventSubscriber(new EasyAdminTabSubscriber());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        //$view->vars['ea_form_tabs'] = $form->getConfig()->getAttribute('ea_form_tabs');
        //$view->vars['ea_form_groups'] = $form->getConfig()->getAttribute('ea_form_groups');

        // some properties and field types require CSS/JS assets to work properly
        // get all property assets and pass them as a form variable
        $allFormFieldAssets = new AssetDto();
        /** @var PropertyDto $propertyDto */
        foreach ($options['entityDto']->getProperties() as $propertyDto) {
            $allFormFieldAssets = $allFormFieldAssets->mergeWith($propertyDto->getAssets());
        }
        //$view->vars['ea_form_assets'] = $allAssets;

        $view->vars['ea_crud_form'] = [
            'assets' => $allFormFieldAssets,
            'entity' => $options['entityDto'],
            'form_tabs' => $form->getConfig()->getAttribute('ea_form_tabs'),
            'form_groups' => $form->getConfig()->getAttribute('ea_form_groups'),
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
                'data_class' => function (Options $options, $dataClass) {
                    return $dataClass ?? $options['entityDto']->getFqcn();
                },
            ])
            ->setDefined(['entityDto'])
            ->setRequired(['entityDto'])
            ->setNormalizer('attr', $this->getAttributesNormalizer());
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ea_crud';
    }

    /**
     * Returns a closure normalizing the form html attributes.
     *
     * @return \Closure
     */
    private function getAttributesNormalizer()
    {
        return function (Options $options, $value) {
            return array_replace([
                'id' => sprintf('%s-%s-form', $options['view'] ?? 'edit', $options['entityDto']->getName()),
            ], $value);
        };
    }
}
