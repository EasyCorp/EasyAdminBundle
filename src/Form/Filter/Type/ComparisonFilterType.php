<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonFilterType extends AbstractType
{
    private $valueType;
    private $valueTypeOptions;
    private $comparisonType;
    private $comparisonTypeOptions;

    public function __construct(string $valueType = null, array $valueTypeOptions = [], string $comparisonType = null, array $comparisonTypeOptions = [])
    {
        $this->valueType = $valueType;
        $this->valueTypeOptions = $valueTypeOptions;
        $this->comparisonType = $comparisonType ?: ComparisonType::class;
        $this->comparisonTypeOptions = $comparisonTypeOptions;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('comparison', $options['comparison_type'], $options['comparison_type_options']);
        $builder->add('value', $options['value_type'], $options['value_type_options'] + [
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('value_type');
        if (null !== $this->valueType) {
            $resolver->setDefault('value_type', $this->valueType);
        }
        $resolver->setDefaults([
            'comparison_type' => $this->comparisonType,
            'comparison_type_options' => $this->comparisonTypeOptions,
            'value_type_options' => $this->valueTypeOptions,
            'error_bubbling' => false,
        ]);
        $resolver->setAllowedTypes('comparison_type', 'string');
        $resolver->setAllowedTypes('comparison_type_options', 'array');
        $resolver->setAllowedTypes('value_type', 'string');
        $resolver->setAllowedTypes('value_type_options', 'array');
    }
}
