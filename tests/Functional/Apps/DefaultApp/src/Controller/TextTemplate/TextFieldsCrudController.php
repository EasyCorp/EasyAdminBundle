<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\TextTemplate;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * Fields and options that let the developer render HTML contents.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class TextFieldsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            'id',
            TextField::new('title'),
            TextField::new('slug')->stripTags(),
            TextareaField::new('content')->renderAsHtml(),
            TextField::new('author.name')->renderAsHtml(),
            Field::new('author.email')->formatValue(static fn (?string $value): string => sprintf('<em>%s</em>', $value)),
            TextField::new('displayTitle')->formatValue(static fn (?string $value): string => sprintf('<u>%s</u>', $value)),
            ChoiceField::new('author.address.city')
                ->setChoices(['City 1' => 'city1'])
                ->formatValue(static fn (?string $value): string => $value ?? '<mark>No city</mark>'),
        ];
    }
}
