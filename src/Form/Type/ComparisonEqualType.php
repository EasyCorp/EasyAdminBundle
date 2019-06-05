<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonEqualType extends AbstractType
{
    public const EQ = '=';
    public const NEQ = '!=';
    public const GT = '>';
    public const GTE = '>=';
    public const LT = '<';
    public const LTE = '<=';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'scalar' => true,
            'choices' => static function (Options $options) {
                $choices = [
                    'label.equal_to' => self::EQ,
                    'label.not_equal_to' => self::NEQ,
                ];

                if ($options['scalar']) {
                    $choices += [
                        'label.greater_than' => self::GT,
                        'label.greater_than_or_equal_to' => self::GTE,
                        'label.less_than' => self::LT,
                        'label.less_than_or_equal_to' => self::LTE,
                    ];
                }

                return $choices;
            },
        ]);
        $resolver->setAllowedTypes('scalar', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
