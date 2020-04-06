<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ChoiceFilterType extends FilterType
{
    use FilterTypeTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $multiple = $builder->get('value')->getOption('multiple');

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) use ($multiple) {
                switch ($data['comparison']) {
                    case ComparisonType::EQ:
                        if (null === $data['value'] || ($multiple && 0 === \count($data['value']))) {
                            $data['comparison'] = 'IS NULL';
                        } else {
                            $data['comparison'] = $multiple ? 'IN' : '=';
                        }
                        break;
                    case ComparisonType::NEQ:
                        if (null === $data['value'] || ($multiple && 0 === \count($data['value']))) {
                            $data['comparison'] = 'IS NOT NULL';
                        } else {
                            $data['comparison'] = $multiple ? 'NOT IN' : '!=';
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
            'comparison_type_options' => ['type' => 'choice'],
            'value_type' => ChoiceType::class,
            'value_type_options' => [
                'multiple' => false,
                'attr' => [
                    'data-widget' => 'select2',
                ],
            ],
        ]);
        $resolver->setNormalizer('value_type_options', static function (Options $options, $value) {
            if (!isset($value['attr'])) {
                $value['attr']['data-widget'] = 'select2';
            }

            return $value;
        });
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
        $paramName = static::createAlias($property);
        $multiple = $form->get('value')->getConfig()->getOption('multiple');
        $data = $form->getData();

        if (null === $data['value'] || ($multiple && 0 === \count($data['value']))) {
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
