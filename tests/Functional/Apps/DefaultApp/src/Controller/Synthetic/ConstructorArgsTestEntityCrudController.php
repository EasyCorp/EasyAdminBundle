<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ConstructorArgsTestEntity;
use Symfony\Component\Form\FormInterface;

/**
 * @extends AbstractCrudController<ConstructorArgsTestEntity>
 */
class ConstructorArgsTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ConstructorArgsTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setFormOptions([
            'empty_data' => static fn (FormInterface $form): ConstructorArgsTestEntity => new ConstructorArgsTestEntity((string) $form->get('name')->getData()),
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
    }

    public function createEntity(string $entityFqcn): ?object
    {
        return null;
    }
}
