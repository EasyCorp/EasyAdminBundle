<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class FormFieldValueController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // these fields format the original value with some options (e.g. max length)
        // and then use the formatValue() method to test that this method receives the
        // original field value, not the one modified with the other options
        return [
            TextField::new('title')->setMaxLength(2)->formatValue(fn ($value, $entity) => $value),
            DateTimeField::new('createdAt')->setFormat('long', 'long')
                ->formatValue(fn (/** @var \DateTimeInterface $value */ $value, $entity) => date('YmdHis', $value->getTimestamp())),
        ];
    }
}
