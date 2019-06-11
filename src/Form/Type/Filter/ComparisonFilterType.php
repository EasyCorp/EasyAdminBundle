<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonEqualType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonFilterType extends FilterType
{
    use FilterTypeTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('cmp', $options['cmp_type'], $options['cmp_type_options']);
        $builder->add('value', $options['value_type'], $options['value_type_options'] + [
            'label' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'cmp_type' => ComparisonEqualType::class,
            'cmp_type_options' => [],
            'value_type' => TextType::class,
            'value_type_options' => [],
        ]);
        $resolver->setAllowedTypes('cmp_type', 'string');
        $resolver->setAllowedTypes('cmp_type_options', 'array');
        $resolver->setAllowedTypes('value_type', 'string');
        $resolver->setAllowedTypes('value_type_options', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $alias = \current($queryBuilder->getRootAliases());
        $property = $metadata['property'];
        $paramName = static::createAlias($property);
        $data = $form->getData();

        $queryBuilder->andWhere(\sprintf('%s.%s %s :%s', $alias, $property, $data['cmp'], $paramName))
            ->setParameter($paramName, $data['value']);
    }
}
