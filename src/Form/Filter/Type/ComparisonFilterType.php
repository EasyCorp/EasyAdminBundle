<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ComparisonFilterType extends AbstractType
{
    private readonly string $comparisonType;

    /**
     * @param array<string, mixed> $valueTypeOptions
     * @param array<string, mixed> $comparisonTypeOptions
     */
    public function __construct(
        private readonly ?string $valueType = null,
        private readonly array $valueTypeOptions = [],
        ?string $comparisonType = null,
        private readonly array $comparisonTypeOptions = [],
    ) {
        $this->comparisonType = $comparisonType ?? ComparisonType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('comparison', $options['comparison_type'], $options['comparison_type_options']);
        $builder->add('value', $options['value_type'], $options['value_type_options'] + [
            'label' => false,
        ]);

        // the data mapper leaves out the fields that couldn't be transformed (e.g. an unknown
        // comparison or an array sent to a text field), so the model transformers of the filter
        // types would read keys that don't exist; failing here stops them and makes the filter invalid
        $builder->addEventListener(FormEvents::SUBMIT, static function (SubmitEvent $event): void {
            foreach ($event->getForm() as $child) {
                if (!$child->isSynchronized()) {
                    throw new TransformationFailedException(sprintf('The "%s" field of the filter is not valid.', $child->getName()));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('value_type');
        if (null !== $this->valueType) {
            $resolver->setDefault('value_type', $this->valueType);
        }
        $resolver->setDefaults([
            'comparison_type' => $this->comparisonType,
            'comparison_type_options' => $this->comparisonTypeOptions,
            'value_type_options' => $this->valueTypeOptions,
            'error_bubbling' => false,
        ]);
        $resolver->setAllowedTypes('comparison_type', 'string');
        $resolver->setAllowedTypes('comparison_type_options', 'array');
        $resolver->setAllowedTypes('value_type', 'string');
        $resolver->setAllowedTypes('value_type_options', 'array');
    }
}
