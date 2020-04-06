<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class DateTimeFilterType extends FilterType
{
    use FilterTypeTrait;

    private $valueType;

    public function __construct(string $valueType = null)
    {
        $this->valueType = $valueType ?: DateTimeType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value2', FormTypeHelper::getTypeClass($options['value_type']), $options['value_type_options'] + [
            'label' => false,
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) use ($options) {
                if (ComparisonType::BETWEEN === $data['comparison']) {
                    if (null === $data['value'] || '' === $data['value'] || null === $data['value2'] || '' === $data['value2']) {
                        throw new TransformationFailedException('Two values must be provided when "BETWEEN" comparison is selected.');
                    }

                    // make sure end datetime is greater than start datetime
                    if ($data['value'] > $data['value2']) {
                        [$data['value'], $data['value2']] = [$data['value2'], $data['value']];
                    }

                    if (DateType::class === $options['value_type']) {
                        $data['value2'] = $data['value2']->format('Y-m-d');
                    } elseif (TimeType::class === $options['value_type']) {
                        $data['value2'] = $data['value2']->format('H:i:s');
                    }
                }

                if ($data['value'] instanceof \DateTimeInterface) {
                    if (DateType::class === $options['value_type']) {
                        // sqlite: Don't include time format for date comparison
                        $data['value'] = $data['value']->format('Y-m-d');
                    } elseif (TimeType::class === $options['value_type']) {
                        // sqlite: Don't include date format for time comparison
                        $data['value'] = $data['value']->format('H:i:s');
                    }
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
            'comparison_type_options' => ['type' => 'datetime'],
            'value_type' => $this->valueType,
            'value_type_options' => [
                'widget' => 'single_text',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'easyadmin_datetime_filter';
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
        $data = $form->getData();

        if (ComparisonType::BETWEEN === $data['comparison']) {
            $paramName1 = static::createAlias($property);
            $paramName2 = static::createAlias($property);
            $queryBuilder->andWhere(sprintf('%s.%s BETWEEN :%s and :%s', $alias, $property, $paramName1, $paramName2))
                ->setParameter($paramName1, $data['value'])
                ->setParameter($paramName2, $data['value2']);
        } else {
            $paramName = static::createAlias($property);
            $queryBuilder->andWhere(sprintf('%s.%s %s :%s', $alias, $property, $data['comparison'], $paramName))
                ->setParameter($paramName, $data['value']);
        }
    }
}
