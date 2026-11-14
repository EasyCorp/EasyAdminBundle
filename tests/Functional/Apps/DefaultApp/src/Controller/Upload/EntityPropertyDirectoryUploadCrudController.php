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
class EntityPropertyDirectoryUploadCrudController extends AbstractCrudController
{
    public const UPLOAD_DIR = 'public/uploads/entity_property_directory/documents/';

    public static function getEntityFqcn(): string
    {
        return FieldTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('textField');
        yield FileField::new('fileField')
            ->setUploadDir(self::UPLOAD_DIR)
            ->setUploadedFileNamePattern(static fn (UploadedFile $file, FieldTestEntity $entity): string => $entity->getTextField().'/[contenthash].[extension]')
            ->setRequired(false);
    }
}
