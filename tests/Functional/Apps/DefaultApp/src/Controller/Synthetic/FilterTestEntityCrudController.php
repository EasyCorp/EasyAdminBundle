<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterTestEntity;

/**
 * @extends AbstractCrudController<FilterTestEntity>
 */
class FilterTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FilterTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(100);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('textFilter');
        yield TextareaField::new('textareaFilter');
        yield IntegerField::new('numericFilter');
        yield NumberField::new('decimalFilter');
        yield DateField::new('dateFilter');
        yield DateTimeField::new('dateTimeFilter');
        yield BooleanField::new('booleanFilter');
        yield ChoiceField::new('choiceFilter')
            ->setChoices([
                'Option A' => 'option_a',
                'Option B' => 'option_b',
                'Option C' => 'option_c',
            ]);
        yield ArrayField::new('arrayFilter');
        yield TextField::new('nullFilter');
        yield AssociationField::new('relatedEntity')->autocomplete();
        yield IntegerField::new('comparisonFilter');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // text filters
            ->add(TextFilter::new('textFilter'))
            ->add(TextFilter::new('textareaFilter'))

            // numeric filters
            ->add(NumericFilter::new('numericFilter'))
            ->add(NumericFilter::new('decimalFilter'))

            // dateTime filters
            ->add(DateTimeFilter::new('dateFilter'))
            ->add(DateTimeFilter::new('dateTimeFilter'))

            // boolean filter
            ->add(BooleanFilter::new('booleanFilter'))

            // choice filter
            ->add(ChoiceFilter::new('choiceFilter')
                ->setChoices([
                    'Option A' => 'option_a',
                    'Option B' => 'option_b',
                    'Option C' => 'option_c',
                ])
            )

            // array filter
            ->add(ArrayFilter::new('arrayFilter')
                ->setChoices([
                    'Tag 1' => 'tag1',
                    'Tag 2' => 'tag2',
                    'Tag 3' => 'tag3',
                ])
                ->canSelectMultiple()
            )

            // null filter
            ->add(NullFilter::new('nullFilter')
                ->setChoiceLabels('Is Null', 'Is Not Null')
            )

            // entity filter (relation)
            ->add(EntityFilter::new('relatedEntity'))

            // comparison filter (generic comparison operators)
            ->add(ComparisonFilter::new('comparisonFilter')
                ->setFormTypeOption('value_type', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class)
            )
        ;
    }
}
