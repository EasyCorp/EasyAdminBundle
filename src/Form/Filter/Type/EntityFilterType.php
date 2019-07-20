<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class EntityFilterType extends FilterType
{
    use FilterTypeTrait;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'entity'],
            'value_type' => EntityType::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ChoiceFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(QueryBuilder $queryBuilder, FormInterface $form, array $metadata)
    {
        $alias = current($queryBuilder->getRootAliases());
        $property = $metadata['property'];
        $paramName = static::createAlias($property);
        $multiple = $form->get('value')->getConfig()->getOption('multiple');
        $data = $form->getData();

        if ('association' === $metadata['dataType'] && $metadata['associationType'] & ClassMetadata::TO_MANY) {
            $assocAlias = static::createAlias($property);
            $queryBuilder->leftJoin(sprintf('%s.%s', $alias, $property), $assocAlias);

            if (0 === \count($data['value'])) {
                $queryBuilder->andWhere(sprintf('%s %s', $assocAlias, $data['comparison']));
            } else {
                $orX = new Expr\Orx();
                $orX->add(sprintf('%s %s (:%s)', $assocAlias, $data['comparison'], $paramName));
                if ('NOT IN' === $data['comparison']) {
                    $orX->add(sprintf('%s IS NULL', $assocAlias));
                }
                $queryBuilder->andWhere($orX)
                    ->setParameter($paramName, $data['value']);
            }
        } elseif (null === $data['value'] || ($multiple && 0 === \count($data['value']))) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $alias, $property, $data['comparison']));
        } else {
            $orX = new Expr\Orx();
            $orX->add(sprintf('%s.%s %s (:%s)', $alias, $property, $data['comparison'], $paramName));
            if (ComparisonType::NEQ === $data['comparison']) {
                $orX->add(sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($orX)
                ->setParameter($paramName, $data['value']);
        }
    }
}
