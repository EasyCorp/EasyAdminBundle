<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;

/**
 * Restricts the uploaded files with the options of FileField and ImageField. The query
 * string defines the restrictions and the validation groups of the form.
 *
 * @extends AbstractCrudController<FieldTestEntity>
 */
class FileUploadRestrictionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FieldTestEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $validationGroup = $this->container->get('request_stack')->getCurrentRequest()?->query->get('validationGroup');
        if (null !== $validationGroup) {
            $crud->setFormOptions(['validation_groups' => [$validationGroup]]);
        }

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $query = $this->getContext()?->getRequest()->query;
        $mimeTypes = $query?->get('mimeTypes');
        $maxSize = $query?->get('maxSize');

        yield IdField::new('id')->hideOnForm();

        foreach ([
            FileField::new('fileField'),
            FileField::new('multipleFileField')->setFormTypeOption('multiple', true),
            ImageField::new('imageField'),
        ] as $field) {
            if (null !== $mimeTypes) {
                $field->mimeTypes($mimeTypes);
            }

            if (null !== $maxSize) {
                $field->maxSize((int) $maxSize);
            }

            yield $field->setUploadDir('public/uploads/')->setRequired(false);
        }
    }
}
