<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class DateFilter extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->get('value')->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) {
                if ($data instanceof \DateTime) {
                    // Don't include time format for date comparison
                    $data = $data->format('Y-m-d');
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
            'value_type' => DateType::class,
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
        return ComparisonFilter::class;
    }
}
