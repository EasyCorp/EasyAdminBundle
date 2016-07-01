<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\Type;

use JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use JavierEguiluz\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Custom form type that deals with some of the logic used to render the
 * forms used to create and edit EasyAdmin entities.
 *
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminFormType extends AbstractType
{
    /** @var ConfigManager */
    private $configManager;

    /** @var TypeConfiguratorInterface[] */
    private $configurators;

    private $formGroups = array();

    /**
     * @param ConfigManager               $configManager
     * @param TypeConfiguratorInterface[] $configurators
     */
    public function __construct(ConfigManager $configManager, array $configurators = array())
    {
        $this->configManager = $configManager;
        $this->configurators = $configurators;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entity = $options['entity'];
        $view = $options['view'];
        $entityConfig = $this->configManager->getEntityConfig($entity);
        $entityProperties = $entityConfig[$view]['fields'];
        $currentFormGroup = null;

        foreach ($entityProperties as $name => $metadata) {
            $formFieldOptions = $metadata['type_options'];

            // Configure options using the list of registered type configurators:
            foreach ($this->configurators as $configurator) {
                if ($configurator->supports($metadata['fieldType'], $formFieldOptions, $metadata)) {
                    $formFieldOptions = $configurator->configure($name, $formFieldOptions, $metadata, $builder);
                }
            }

            $formFieldType = LegacyFormHelper::getType($metadata['fieldType']);

            // if the form field is a special 'group' design element, don't add to the
            // form fields, set it as the current form group (for the subsequent form
            // fields) and store the form 'group' details in a special property
            if ('JavierEguiluz\\Bundle\\EasyAdminBundle\\Form\\Type\\EasyAdminGroupType' === $formFieldType) {
                $currentFormGroup = $metadata['fieldName'];
                $this->formGroups[$currentFormGroup] = $metadata;

                continue;
            }

            $formFieldOptions['attr']['formGroup'] = $currentFormGroup;

            // 'divider' and 'section' are 'fake' form fields used to create the design
            // elements of the complex form layouts: define them as unmapped and non-required
            if (preg_match('/^_easyadmin_form_design_element_\d+/', $metadata['property'])) {
                $formFieldOptions['mapped'] = false;
                $formFieldOptions['required'] = false;
            }

            $builder->add($name, $formFieldType, $formFieldOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['easyadmin_form_groups'] = $this->formGroups;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $configManager = $this->configManager;

        $resolver
            ->setDefaults(array(
                'allow_extra_fields' => true,
                'data_class' => function (Options $options) use ($configManager) {
                    $entity = $options['entity'];
                    $entityConfig = $configManager->getEntityConfig($entity);

                    return $entityConfig['class'];
                },
            ))
            ->setRequired(array('entity', 'view'));

        if (LegacyFormHelper::useLegacyFormComponent()) {
            $resolver->setNormalizers(array('attr' => $this->getAttributesNormalizer()));
        } else {
            $resolver->setNormalizer('attr', $this->getAttributesNormalizer());
        }
    }

    // BC for SF < 2.7
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'easyadmin';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * Returns a closure normalizing the form html attributes.
     *
     * @return \Closure
     */
    private function getAttributesNormalizer()
    {
        return function (Options $options, $value) {
            return array_replace(array(
                'id' => sprintf('%s-%s-form', $options['view'], strtolower($options['entity'])),
            ), $value);
        };
    }
}
