<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;

/**
 * @extends AbstractCrudController<FieldTestEntity>
 */
class DatedDirectoryUploadCrudController extends AbstractCrudController
{
    public const UPLOAD_DIR = 'public/uploads/dated_directory/documents/';

    public static function getEntityFqcn(): string
    {
        return FieldTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FileField::new('fileField')
            ->setUploadDir(self::UPLOAD_DIR)
            ->setUploadedFileNamePattern('[YYYY]/[MM]/[name].[extension]')
            ->setRequired(false);
        yield FileField::new('multipleFileField')
            ->setFormTypeOption('multiple', true)
            ->setUploadDir(self::UPLOAD_DIR)
            ->setRequired(false);
    }
}
