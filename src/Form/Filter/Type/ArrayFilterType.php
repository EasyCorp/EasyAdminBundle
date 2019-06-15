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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('value', $options['value_type'], $options['value_type_options'] + [
            'label' => false,
            'choice_loader' => new DynamicChoiceLoader(),
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) {
                if ([] === $data['value']) {
                    $data['comparison'] = ComparisonType::CONTAINS === $data['comparison'] ? 'IS NULL' : 'IS NOT NULL';
                }

                return $data;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
    public function getParent()
    {
        return ComparisonFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $alias = \current($queryBuilder->getRootAliases());
        $property = $metadata['property'];
        $paramName = static::createAlias($property);
        $useQuotes = 'simple_array' !== $metadata['dataType'];
        $data = $form->getData();

        if ([] === $data['value']) {
            $queryBuilder->andWhere(\sprintf('%s.%s %s', $alias, $property, $data['comparison']));
        } else {
            $orX = new Expr\Orx();
            foreach ($data['value'] as $value) {
                $orX->add(\sprintf('%s.%s %s :%s', $alias, $property, $data['comparison'], $paramName));
                $queryBuilder->setParameter($paramName, $useQuotes ? '%"'.$value.'"%' : '%'.$value.'%');
            }
            if (ComparisonType::NOT_CONTAINS === $data['comparison']) {
                $orX->add(\sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($orX);
        }
    }
}
