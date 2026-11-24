<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use Symfony\Component\Validator\Constraints\File;

/**
 * Restricts the uploaded files with the options of ImageField. The query string
 * defines the restrictions and the validation groups of the form.
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
        $maxSize = $this->getContext()?->getRequest()->query->get('maxSize');

        yield IdField::new('id')->hideOnForm();

        $imageField = ImageField::new('imageField')
            ->setUploadDir('public/uploads/upload_restrictions/')
            ->setRequired(false);

        if (null !== $maxSize) {
            $imageField->setFileConstraints(new File(maxSize: (int) $maxSize));
        }

        yield $imageField;
    }
}
