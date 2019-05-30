<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonLikeType extends AbstractType
{
    public const CONTAINS = 'like';
    public const NOT_CONTAINS = 'not like';
    public const STARTS_WITH = 'like*';
    public const ENDS_WITH = '*like';
    public const EXACTLY = '=';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'choices' => [
                'label.contains' => self::CONTAINS,
                'label.not_contains' => self::NOT_CONTAINS,
                'label.starts_with' => self::STARTS_WITH,
                'label.ends_with' => self::ENDS_WITH,
                'label.exactly' => self::EXACTLY,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
