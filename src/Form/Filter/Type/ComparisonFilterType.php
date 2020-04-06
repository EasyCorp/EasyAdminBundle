<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonFilterType extends FilterType
{
    use FilterTypeTrait;

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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('comparison', $options['comparison_type'], $options['comparison_type_options']);
        $builder->add('value', FormTypeHelper::getTypeClass($options['value_type']), $options['value_type_options'] + [
            'label' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $alias = current($queryBuilder->getRootAliases());
        $property = $metadata['property'];
        $paramName = static::createAlias($property);
        $data = $form->getData();

        $queryBuilder->andWhere(sprintf('%s.%s %s :%s', $alias, $property, $data['comparison'], $paramName))
            ->setParameter($paramName, $data['value']);
    }
}
