<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\NumericFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Configurator\TypeConfiguratorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FiltersFormType extends AbstractType
{
    private $configurators;

    /**
     * @param TypeConfiguratorInterface[] $configurators
     */
    public function __construct(iterable $configurators)
    {
        $this->configurators = $configurators;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // TODO: get the real configured filters
        $configuredFilters = [
            'enabled' => ['type' => BooleanFilterType::class, 'type_options' => []],
            'price' => ['type' => NumericFilterType::class, 'type_options' => []],
            'name' => ['type' => TextFilterType::class, 'type_options' => []],
        ];
        foreach ($configuredFilters as $propertyName => $filterConfig) {
            $formFieldOptions = $filterConfig['type_options'];

            /*
             * TODO: enable this:

            // Configure options using the list of registered type configurators:
            foreach ($this->configurators as $configurator) {
                if ($configurator->supports($filterConfig['type'], $formFieldOptions, $filterConfig)) {
                    $formFieldOptions = $configurator->configure($propertyName, $formFieldOptions, $filterConfig, $builder);
                }
            }
            */

            $builder->add($propertyName, $filterConfig['type'], $formFieldOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'ea_filters';
    }
}
