<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;

/**
 * @extends AbstractCrudController<FieldTestEntity>
 */
class NameAsDirectoryUploadCrudController extends AbstractCrudController
{
    public const UPLOAD_DIR = 'public/uploads/name_as_directory/documents/';

    public static function getEntityFqcn(): string
    {
        return FieldTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        foreach ([
            FileField::new('fileField'),
            FileField::new('multipleFileField')->setFormTypeOption('multiple', true),
            ImageField::new('imageField'),
        ] as $field) {
            yield $field
                ->setUploadDir(self::UPLOAD_DIR)
                ->setUploadedFileNamePattern('[name]/[contenthash].[extension]')
                ->setRequired(false);
        }
    }
}
