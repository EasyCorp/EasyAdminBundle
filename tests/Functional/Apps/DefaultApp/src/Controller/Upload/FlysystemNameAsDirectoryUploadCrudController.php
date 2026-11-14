<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Upload;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @extends AbstractCrudController<FieldTestEntity>
 */
class FlysystemNameAsDirectoryUploadCrudController extends AbstractCrudController
{
    public const STORAGE = 'name_as_directory.storage';
    public const UPLOAD_DIR = 'documents/';

    public static function getEntityFqcn(): string
    {
        return FieldTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('textField');
        yield FileField::new('fileField')
            ->setFlysystemStorage(self::STORAGE)
            ->setFlysystemUrlPrefix('/storage/')
            ->setUploadDir(self::UPLOAD_DIR)
            ->setUploadedFileNamePattern('[name]/[name]/[contenthash].[extension]')
            ->setRequired(false);
        yield FileField::new('multipleFileField')
            ->setFormTypeOption('multiple', true)
            ->setFlysystemStorage(self::STORAGE)
            ->setFlysystemUrlPrefix('/storage/')
            ->setUploadDir(self::UPLOAD_DIR)
            ->setUploadedFileNamePattern(static fn (UploadedFile $file, FieldTestEntity $entity): string => ($entity->getTextField() ?? '[name]').'/[contenthash].[extension]')
            ->setRequired(false);
    }
}
