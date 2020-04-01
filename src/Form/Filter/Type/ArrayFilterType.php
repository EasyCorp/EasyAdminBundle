<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\ChoiceList\Loader\DynamicChoiceLoader;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ArrayFilterType extends AbstractType
{
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
}
