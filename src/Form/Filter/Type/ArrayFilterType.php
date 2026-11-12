<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\ChoiceList\Loader\DynamicChoiceLoader;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ArrayFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultOptions = ['label' => false];
        if (!isset($options['value_type_options']['choices']) || [] === $options['value_type_options']['choices']) {
            $defaultOptions += ['choice_loader' => new DynamicChoiceLoader()];
        }
        $builder->add('value', $options['value_type'], $options['value_type_options'] + $defaultOptions);

        $builder->addModelTransformer(new CallbackTransformer(
            static fn ($data) => $data,
            static function ($data) {
                // without a comparison the filter can't be applied, so it's left as is to be ignored later
                if (null === $data['comparison']) {
                    return $data;
                }

                if (null === $data['value'] || [] === $data['value']) {
                    $data['comparison'] = \in_array($data['comparison'], [ComparisonType::CONTAINS, ComparisonType::CONTAINS_ALL], true) ? 'IS NULL' : 'IS NOT NULL';
                } else {
                    $data['value'] = (array) $data['value'];
                }

                return $data;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'array'],
            'value_type' => ChoiceType::class,
            'value_type_options' => [
                'multiple' => true,
                'attr' => [
                    'data-ea-widget' => 'ea-autocomplete',
                    'data-ea-autocomplete-allow-item-create' => 'true',
                ],
            ],
        ]);
        $resolver->setNormalizer('value_type_options', static function (Options $options, array $value): array {
            // options set by the filter (e.g. its choices) replace the whole default array,
            // so the defaults that were not set explicitly are restored here
            $value += ['multiple' => true];

            if (true === ($value['expanded'] ?? false) || isset($value['attr'])) {
                return $value;
            }

            $value['attr'] = ['data-ea-widget' => 'ea-autocomplete'];
            // new items can only be created when the filter doesn't define a fixed list of choices
            if (!isset($value['choices']) || [] === $value['choices']) {
                $value['attr']['data-ea-autocomplete-allow-item-create'] = 'true';
            }

            return $value;
        });
    }

    public function getParent(): string
    {
        return ComparisonFilterType::class;
    }
}
