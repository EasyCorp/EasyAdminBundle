<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
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
    private string $valueType;

    public function __construct(?string $valueType = null)
    {
        $this->valueType = $valueType ?? DateTimeType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value2', $options['value_type'], $options['value_type_options'] + [
            'label' => false,
        ]);

        $builder->addModelTransformer(new CallbackTransformer(
            static fn ($data) => $data,
            static function ($data) use ($options) {
                // Symfony Form will cut off invalid values, so make sure no warnings will be thrown out
                $data['value'] ??= null;
                $data['value2'] ??= null;

                if (ComparisonType::BETWEEN === $data['comparison']) {
                    if (null === $data['value'] || '' === $data['value'] || null === $data['value2'] || '' === $data['value2']) {
                        throw new TransformationFailedException('Two values must be provided when "BETWEEN" comparison is selected.');
                    }

                    // make sure end datetime is greater than start datetime
                    if ($data['value'] > $data['value2']) {
                        [$data['value'], $data['value2']] = [$data['value2'], $data['value']];
                    }

                    if (DateType::class === $options['value_type']) {
                        $data['value2'] = $data['value2']->format('Y-m-d');
                    } elseif (TimeType::class === $options['value_type']) {
                        $data['value2'] = $data['value2']->format('H:i:s');
                    }
                }

                if ($data['value'] instanceof \DateTimeInterface) {
                    if (DateType::class === $options['value_type']) {
                        // sqlite: Don't include time format for date comparison
                        $data['value'] = $data['value']->format('Y-m-d');
                    } elseif (TimeType::class === $options['value_type']) {
                        // sqlite: Don't include date format for time comparison
                        $data['value'] = $data['value']->format('H:i:s');
                    }
                }

                return $data;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'datetime'],
            'value_type' => $this->valueType,
            'value_type_options' => [
                'widget' => 'single_text',
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'ea_datetime_filter';
    }

    public function getParent(): string
    {
        return ComparisonFilterType::class;
    }
}
