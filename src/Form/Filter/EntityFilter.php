<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonEqualType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EntityFilter extends Filter
{
    use FilterTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) use ($builder) {
                $multiple = $builder->get('value')->getOption('multiple');

                switch ($data['cmp']) {
                    case ComparisonEqualType::EQ:
                        if (null === $data['value'] || (\is_iterable($data['value']) && 0 === \count($data['value']))) {
                            $data['cmp'] = 'IS NULL';
                        } else {
                            $data['cmp'] = $multiple ? 'IN' : '=';
                        }
                        break;
                    case ComparisonEqualType::NEQ:
                        if (null === $data['value'] || (\is_iterable($data['value']) && 0 === \count($data['value']))) {
                            $data['cmp'] = 'IS NOT NULL';
                        } else {
                            $data['cmp'] = $multiple ? 'NOT IN' : '!=';
                        }
                        break;
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
            'cmp_type_options' => ['scalar' => false],
            'value_type' => EntityType::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ComparisonFilter::class;
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

        if ('association' === $metadata['dataType'] && $metadata['associationType'] & ClassMetadata::TO_MANY) {
            $assocAlias = static::createAlias($property);
            $queryBuilder->leftJoin(\sprintf('%s.%s', $alias, $property), $assocAlias);

            if (0 === \count($data['value'])) {
                $queryBuilder->andWhere(\sprintf('%s %s', $assocAlias, $data['cmp']));
            } else {
                $orX = new Expr\Orx();
                $orX->add(\sprintf('%s %s (:%s)', $assocAlias, $data['cmp'], $paramName));
                if ('NOT IN' === $data['cmp']) {
                    $orX->add(\sprintf('%s IS NULL', $assocAlias));
                }
                $queryBuilder->andWhere($orX)
                    ->setParameter($paramName, $data['value']);
            }
        } elseif (null === $data['value']) {
            $queryBuilder->andWhere(\sprintf('%s.%s %s', $alias, $property, $data['cmp']));
        } else {
            $orX = new Expr\Orx();
            $orX->add(\sprintf('%s.%s %s (:%s)', $alias, $property, $data['cmp'], $paramName));
            if (ComparisonEqualType::NEQ === $data['cmp']) {
                $orX->add(\sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($orX)
                ->setParameter($paramName, $data['value']);
        }
    }
}
