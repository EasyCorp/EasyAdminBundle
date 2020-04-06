<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class TextFilterType extends AbstractType
{
    private $valueType;

    public function __construct(string $valueType = null)
    {
        $this->valueType = $valueType ?: TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static function ($data) { return $data; },
            static function ($data) {
                switch ($data['comparison']) {
                    case ComparisonType::STARTS_WITH:
                        $data['comparison'] = ComparisonType::CONTAINS;
                        $data['value'] .= '%';
                        break;
                    case ComparisonType::ENDS_WITH:
                        $data['comparison'] = ComparisonType::CONTAINS;
                        $data['value'] = '%'.$data['value'];
                        break;
                    case ComparisonType::CONTAINS:
                    case ComparisonType::NOT_CONTAINS:
                        $data['value'] = '%'.$data['value'].'%';
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
            'comparison_type_options' => ['type' => 'text'],
            'value_type' => $this->valueType,
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
