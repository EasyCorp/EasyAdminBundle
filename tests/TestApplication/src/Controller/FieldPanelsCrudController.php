<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

class FieldPanelsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // this field is out of any panel on purpose
            IdField::new('id'),
            FormField::addPanel('Panel 1')->setIcon('fa fa-cog')->addCssClass('bg-info'),
            TextField::new('title'),
            FormField::addPanel('Panel 2')->setIcon('fa fa-user')->addCssClass('bg-warning'),
            TextField::new('slug'),
            // this panel is added after all fields on purpose
            FormField::addPanel('Panel 3')->setIcon('fa fa-file-alt')->addCssClass('bg-danger'),
        ];
    }
}
