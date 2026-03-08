<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;
use function Symfony\Component\Translation\t;

/**
 * Used to test the ->setHelp() method of fields and how that
 * help message is rendered in the form.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class FormFieldHelpController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // fields with no help defined
            FormField::addTab('Tab 1'),
            FormField::addColumn()->addCssClass('column-1'),
            FormField::addFieldset('Fieldset 1'),
            IdField::new('id'),
            TextField::new('title')->setHelp(''),

            // fields with help defined as simple text strings
            FormField::addTab('Tab 2')->setHelp('Tab 2 Lorem Ipsum'),
            FormField::addColumn()->addCssClass('column-2')->setHelp('Column 2 Lorem Ipsum'),
            FormField::addFieldset('Fieldset 2')->setHelp('Fieldset 2 Lorem Ipsum'),
            TextField::new('slug')->setHelp('Slug Lorem Ipsum'),

            // fields with help defined as text strings with HTML contents
            FormField::addTab('Tab 3')->setHelp('<a href="https://example.com">Tab 3</a> <b>Lorem</b> Ipsum'),
            FormField::addColumn()->addCssClass('column-3')->setHelp('<a href="https://example.com">Column 3</a> <b>Lorem</b> Ipsum'),
            FormField::addFieldset('Fieldset 3')->setHelp('<a href="https://example.com">Fieldset 3</a> <b>Lorem</b> Ipsum'),
            TextField::new('content')->setHelp('<a href="https://example.com">Content</a> <b>Lorem</b> Ipsum'),

            // fields with help defined as Translatable objects using simple text strings
            FormField::addTab('Tab 4')->setHelp(t('Tab 4 Lorem Ipsum')),
            FormField::addColumn()->addCssClass('column-4')->setHelp(t('Column 4 Lorem Ipsum')),
            FormField::addFieldset('Fieldset 4')->setHelp(t('Fieldset 4 Lorem Ipsum')),
            DateTimeField::new('createdAt')->setHelp(t('CreatedAt Lorem Ipsum')),

            // fields with help defined as Translatable objects using text strings with HTML contents
            FormField::addTab('Tab 5')->setHelp(t('<a href="https://example.com">Tab 5</a> <b>Lorem</b> Ipsum')),
            FormField::addColumn()->addCssClass('column-5')->setHelp(t('<a href="https://example.com">Column 5</a> <b>Lorem</b> Ipsum')),
            FormField::addFieldset('Fieldset 5')->setHelp(t('<a href="https://example.com">Fieldset 5</a> <b>Lorem</b> Ipsum')),
            DateTimeField::new('publishedAt')->setHelp(t('<a href="https://example.com">PublishedAt</a> <b>Lorem</b> Ipsum')),
        ];
    }
}
