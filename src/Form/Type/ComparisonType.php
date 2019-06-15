<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonType extends AbstractType
{
    public const EQ = '=';
    public const NEQ = '!=';
    public const GT = '>';
    public const GTE = '>=';
    public const LT = '<';
    public const LTE = '<=';
    public const CONTAINS = 'like';
    public const NOT_CONTAINS = 'not like';
    public const STARTS_WITH = 'like*';
    public const ENDS_WITH = '*like';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'type' => 'numeric',
            'choices' => static function (Options $options) {
                $choices = [];
                switch ($options['type']) {
                    case 'numeric':
                        $choices = [
                            'label.is_equal_to' => self::EQ,
                            'label.is_not_equal_to' => self::NEQ,
                            'label.is_greater_than' => self::GT,
                            'label.is_greater_than_or_equal_to' => self::GTE,
                            'label.is_less_than' => self::LT,
                            'label.is_less_than_or_equal_to' => self::LTE,
                        ];
                        break;
                    case 'text':
                        $choices = [
                            'label.contains' => self::CONTAINS,
                            'label.not_contains' => self::NOT_CONTAINS,
                            'label.starts_with' => self::STARTS_WITH,
                            'label.ends_with' => self::ENDS_WITH,
                            'label.exactly' => self::EQ,
                            'label.not_exactly' => self::NEQ,
                        ];
                        break;
                    case 'datetime':
                        $choices = [
                            'label.is_same' => self::EQ,
                            'label.is_not_same' => self::NEQ,
                            'label.is_after' => self::GT,
                            'label.is_after_or_same' => self::GTE,
                            'label.is_before' => self::LT,
                            'label.is_before_or_same' => self::LTE,
                        ];
                        break;
                    case 'array':
                        $choices = [
                            'label.is_same' => self::CONTAINS,
                            'label.is_not_same' => self::NOT_CONTAINS,
                        ];
                        break;
                    case 'entity':
                        $choices = [
                            'label.is_same' => self::EQ,
                            'label.is_not_same' => self::NEQ,
                        ];
                        break;
                }

                return $choices;
            },
            'translation_domain' => 'EasyAdminBundle',
        ]);
        $resolver->setAllowedTypes('type', 'string');
        $resolver->setAllowedValues('type', ['numeric', 'text', 'datetime', 'array', 'entity']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
