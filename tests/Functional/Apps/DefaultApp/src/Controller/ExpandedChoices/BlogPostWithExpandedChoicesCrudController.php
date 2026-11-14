<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithExpandedChoicesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield AssociationField::new('categories', '<b>Categories</b>')->setFormTypeOptions(['expanded' => true, 'choice_label' => 'name']);
        yield AssociationField::new('author')->setFormTypeOption('expanded', true);
        yield Field::new('publisher')->setFormType(EntityType::class)->setFormTypeOptions(['class' => User::class, 'expanded' => true, 'placeholder' => '<i>None</i>']);
        yield ChoiceField::new('slug')->setChoices(['<b>Choice 1</b>' => 'choice-1'])->renderExpanded();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('categories')->setFormTypeOption('value_type_options.expanded', true)->setFormTypeOption('value_type_options.choice_label', 'name'));
    }
}
