<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FiltersFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $nameMapping = [];

        /** @var FilterDto $filter */
        foreach ($options['ea_filters'] as $filterName => $filter) {
            $normalizedName = str_replace('.', '_', $filterName);
            $nameMapping[$normalizedName] = $filterName;

            $builder->add($normalizedName, $filter->getFormType(), $filter->getFormTypeOptions());
        }

        $builder->setAttribute('ea_filter_name_mapping', $nameMapping);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('ea_filters');
        $resolver->setAllowedTypes('ea_filters', FilterCollection::class);

        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
            'ea_filters' => FilterCollection::new(),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'ea_filters';
    }
}
