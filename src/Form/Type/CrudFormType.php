<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use ArrayObject;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminTabSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
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

    /**
     * @param TypeConfiguratorInterface[] $typeConfigurators
     */
    public function __construct(iterable $typeConfigurators)
    {
        $this->typeConfigurators = $typeConfigurators;
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

            // Configure options using the list of registered type configurators:
            foreach ($this->typeConfigurators as $configurator) {
                if ($configurator->supports($propertyDto->getFormType(), $formFieldOptions, $propertyDto)) {
                    $formFieldOptions = $configurator->configure($name, $formFieldOptions, $propertyDto, $builder);
                }
            }

            $formFieldType = $propertyDto->getFormType();

            // if the form field is a special 'group' design element, don't add it
            // to the form. Instead, consider it the current form group (this is
            // applied to the form fields defined after it) and store its details
            // in a property to get them in form template
            if (\in_array($formFieldType, ['easyadmin_group', EasyAdminGroupType::class])) {
                $metadata['form_tab'] = $currentFormTab ?: null;
                $currentFormGroup = $metadata['fieldName'];
                $formGroups[$currentFormGroup] = $metadata;

                continue;
            }

            // if the form field is a special 'tab' design element, don't add it
            // to the form. Instead, consider it the current form group (this is
            // applied to the form fields defined after it) and store its details
            // in a property to get them in form template
            if (\in_array($formFieldType, ['easyadmin_tab', EasyAdminTabType::class])) {
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
            if (0 === strpos($propertyDto->getName(), '_easyadmin_form_design_element_')) {
                $formFieldOptions['mapped'] = false;
                $formFieldOptions['required'] = false;
            }

            $formField = $builder->getFormFactory()->createNamedBuilder($name, $formFieldType, null, $formFieldOptions);
            $formField->setAttribute('easyadmin_form_tab', $currentFormTab);
            $formField->setAttribute('easyadmin_form_group', $currentFormGroup);

            $builder->add($formField);
        }

        $builder->setAttribute('easyadmin_form_tabs', $formTabs);
        $builder->setAttribute('easyadmin_form_groups', $formGroups);

        if (\count($formTabs) > 0) {
            $builder->addEventSubscriber(new EasyAdminTabSubscriber());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['easyadmin_form_tabs'] = $form->getConfig()->getAttribute('easyadmin_form_tabs');
        $view->vars['easyadmin_form_groups'] = $form->getConfig()->getAttribute('easyadmin_form_groups');
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
                    if (null !== $dataClass) {
                        return $dataClass;
                    }

                    return $options['entityDto']->getFqcn();
                },
            ])
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
