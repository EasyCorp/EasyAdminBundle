<?= "<?php\n"; ?>

namespace <?= $crud_controller_namespace; ?>;

use <?= $entity_fqcn; ?>;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class <?= $crud_controller_class_name; ?> extends AbstractCrudController
{
    public static $entityFqcn = <?= $entity_class_name; ?>::class;

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
