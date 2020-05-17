<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $entity_fqcn; ?>;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class <?= $class_name; ?> extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return <?= $entity_class_name; ?>::class;
    }

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
