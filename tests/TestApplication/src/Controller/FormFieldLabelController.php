<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;
use function Symfony\Component\Translation\t;

/**
 * Used to test the different types of labels that fields can configure.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class FormFieldLabelController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // the custom CSS classes are needed so we can select the fields in tests

            // fields with no label defined
            FormField::addTab(),
            FormField::addColumn()->addCssClass('column-1'),
            FormField::addFieldset()->addCssClass('fieldset-1'),
            IdField::new('id')->addCssClass('field-id'),

            // fields with a NULL label defined
            FormField::addTab(label: null)->addCssClass('tab-2'),
            FormField::addColumn(label: null)->addCssClass('column-2'),
            FormField::addFieldset(label: null)->addCssClass('fieldset-2'),
            TextField::new('title', null)->addCssClass('field-title'),

            // fields with a FALSE label defined
            FormField::addTab(label: false),
            FormField::addColumn(label: false)->addCssClass('column-3'),
            FormField::addFieldset(label: false)->addCssClass('fieldset-3'),
            TextField::new('slug', false)->addCssClass('field-slug'),

            // fields with a label defined as an empty string
            FormField::addTab(label: ''),
            FormField::addColumn(label: '')->addCssClass('column-4'),
            FormField::addFieldset(label: '')->addCssClass('fieldset-4'),
            TextField::new('content', '')->addCssClass('field-content'),

            // fields with a label defined as a text string
            FormField::addTab('Tab 5'),
            FormField::addColumn(label: 'Column 5')->addCssClass('column-5'),
            FormField::addFieldset(label: 'Fieldset 5')->addCssClass('fieldset-5'),
            DateTimeField::new('createdAt', 'Lorem Ipsum 1')->addCssClass('field-created-at'),

            // fields with a label defined as a translatable string
            FormField::addTab(t('Tab 6')),
            FormField::addColumn(label: t('Column 6'))->addCssClass('column-6'),
            FormField::addFieldset(label: t('Fieldset 6'))->addCssClass('fieldset-6'),
            DateTimeField::new('publishedAt', t('PublishedAt Lorem Ipsum'))->addCssClass('field-published-at'),

            // fields with a label defined as a string with HTML contents
            // don't use <a> tags in the tab label because tabs are rendered inside <a> tags and that causes issues
            FormField::addTab('<span class="text-danger">Tab</span> <b>7</b>'),
            FormField::addColumn(label: '<a href="https://example.com">Column</a> <b>7</b>')->addCssClass('column-7'),
            FormField::addFieldset(label: '<a href="https://example.com">Fieldset</a> <b>7</b>')->addCssClass('fieldset-7'),
            AssociationField::new('author', '<a href="https://example.com">Author</a> <b>Lorem</b> Ipsum')->addCssClass('field-author'),

            // fields with a label defined as a translatable string with HTML contents
            // don't use <a> tags in the tab label because tabs are rendered inside <a> tags and that causes issues
            FormField::addTab(t('<span class="text-danger">Tab</span> <b>8</b>')),
            FormField::addColumn(label: t('<a href="https://example.com">Column</a> <b>8</b>'))->addCssClass('column-8'),
            FormField::addFieldset(label: t('<a href="https://example.com">Fieldset</a> <b>8</b>'))->addCssClass('fieldset-8'),
            AssociationField::new('publisher', t('<a href="https://example.com">Publisher</a> <b>Lorem</b> Ipsum'))->addCssClass('field-publisher'),
        ];
    }
}
