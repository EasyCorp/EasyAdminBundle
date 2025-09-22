<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class FormFieldsetsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // this field is out of any fieldset on purpose
            IdField::new('id'),
            FormField::addFieldset('Fieldset 1')->setIcon('fa fa-cog')->addCssClass('bg-info'),
            TextField::new('title'),
            FormField::addFieldset('Fieldset 2')->setIcon('fa fa-user')->addCssClass('bg-warning'),
            TextField::new('slug'),
            // this fieldset is added after all fields on purpose
            FormField::addFieldset('Fieldset 3')->setIcon('fa fa-file-alt')->addCssClass('bg-danger'),
        ];
    }
}
