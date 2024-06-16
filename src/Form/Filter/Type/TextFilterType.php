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
 *
 * @internal Don't use this to define a text filter. Use Filter\TextFilter instead.
 */
class TextFilterType extends AbstractType
{
    private string $valueType;

    public function __construct(?string $valueType = null)
    {
        $this->valueType = $valueType ?? TextType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static fn ($data) => $data,
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'text'],
            'value_type' => $this->valueType,
        ]);
    }

    public function getParent(): string
    {
        return ComparisonFilterType::class;
    }
}
