<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\ChoiceList\Loader\DynamicChoiceLoader;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ArrayFilterType extends FilterType
{
    use FilterTypeTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultOptions = ['label' => false];
        if (!isset($options['value_type_options']['choices']) || [] === $options['value_type_options']['choices']) {
            $defaultOptions += ['choice_loader' => new DynamicChoiceLoader()];
        }
        $builder->add('value', $options['value_type'], $options['value_type_options'] + $defaultOptions);

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) {
                if (null === $data['value'] || [] === $data['value']) {
                    $data['comparison'] = ComparisonType::CONTAINS === $data['comparison'] ? 'IS NULL' : 'IS NOT NULL';
                } else {
                    $data['value'] = (array) $data['value'];
                }

                return $data;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'array'],
            'value_type' => ChoiceType::class,
            'value_type_options' => [
                'multiple' => true,
                'attr' => [
                    'data-widget' => 'select2',
                    'data-select2-tags' => 'true',
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ComparisonFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $alias = current($queryBuilder->getRootAliases());
        $property = $metadata['property'];
        $useQuotes = 'simple_array' !== $metadata['dataType'];
        $data = $form->getData();

        if (null === $data['value'] || [] === $data['value']) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $alias, $property, $data['comparison']));
        } else {
            $orX = new Expr\Orx();
            foreach ($data['value'] as $value) {
                $paramName = static::createAlias($property);
                $orX->add(sprintf('%s.%s %s :%s', $alias, $property, $data['comparison'], $paramName));
                $queryBuilder->setParameter($paramName, $useQuotes ? '%"'.$value.'"%' : '%'.$value.'%');
            }
            if (ComparisonType::NOT_CONTAINS === $data['comparison']) {
                $orX->add(sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($orX);
        }
    }
}
