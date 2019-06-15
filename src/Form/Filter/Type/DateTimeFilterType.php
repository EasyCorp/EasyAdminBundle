<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class DateTimeFilterType extends AbstractType
{
    private $valueType;

    public function __construct(string $valueType = null)
    {
        $this->valueType = $valueType ?: DateTimeType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->get('value')->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) use ($options) {
                if ($data instanceof \DateTime) {
                    if (DateType::class === $options['value_type']) {
                        // sqlite: Don't include time format for date comparison
                        $data = $data->format('Y-m-d');
                    } elseif (TimeType::class === $options['value_type']) {
                        // sqlite: Don't include date format for time comparison
                        $data = $data->format('H:i:s');
                    }
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
    public function getParent()
    {
        return ComparisonFilterType::class;
    }
}
