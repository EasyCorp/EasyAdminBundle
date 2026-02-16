<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class FiltersFormType extends AbstractType
{
    /**
     * Embedded properties use '.' in their name (e.g. "address.city"), but Symfony
     * forms don't allow dots in field names, so we replace them with this separator.
     *
     * @see EntityRepository::addFilterClause()
     */
    public const EMBEDDED_PROPERTY_SEPARATOR = ':';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var FilterDto $filter */
        foreach ($options['ea_filters'] as $filter) {
            $name = str_replace('.', self::EMBEDDED_PROPERTY_SEPARATOR, $filter->getProperty());
            $builder->add($name, $filter->getFormType(), $filter->getFormTypeOptions());
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('ea_filters');
        $resolver->setAllowedTypes('ea_filters', FilterCollection::class);

        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
            'ea_filters' => new FilterCollection(),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'ea_filters';
    }
}
