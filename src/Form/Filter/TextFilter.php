<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonLikeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class TextFilter extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) {
                switch ($data['cmp']) {
                    case ComparisonLikeType::STARTS_WITH:
                        $data['cmp'] = ComparisonLikeType::CONTAINS;
                        $data['value'] .= '%';
                        break;
                    case ComparisonLikeType::ENDS_WITH:
                        $data['cmp'] = ComparisonLikeType::CONTAINS;
                        $data['value'] = '%'.$data['value'];
                        break;
                    case ComparisonLikeType::EXACTLY:
                        // no-op
                        break;
                    default:
                        $data['value'] = '%'.$data['value'].'%';
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
        $resolver->setDefault('cmp_type', ComparisonLikeType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ComparisonFilter::class;
    }
}
