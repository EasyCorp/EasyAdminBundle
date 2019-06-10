<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EasyAdminFiltersFormType extends AbstractType
{
    private $configManager;
    private $configurators;

    /**
     * @param TypeConfiguratorInterface[] $configurators
     */
    public function __construct(ConfigManager $configManager, array $configurators = [])
    {
        $this->configManager = $configManager;
        $this->configurators = $configurators;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $entityConfig = $this->configManager->getEntityConfig($options['entity']);

        foreach ($entityConfig['list']['filters'] as $propertyName => $filterConfig) {
            $formFieldOptions = $filterConfig['type_options'];

            // Configure options using the list of registered type configurators:
            foreach ($this->configurators as $configurator) {
                if ($configurator->supports($filterConfig['type'], $formFieldOptions, $filterConfig)) {
                    $formFieldOptions = $configurator->configure($propertyName, $formFieldOptions, $filterConfig, $builder);
                }
            }

            $builder->add($propertyName, $filterConfig['type'], $formFieldOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('entity');
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
        $resolver->setAllowedTypes('entity', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'easyadmin_filters';
    }
}
