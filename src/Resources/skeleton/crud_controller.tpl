<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?php foreach ($uses as $use) { ?>
use <?= $use; ?>;
<?php } ?>

/**
 * @extends AbstractCrudController<<?= $entity_class_name; ?>>
 */
class <?= $class_name; ?> extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return <?= $entity_class_name; ?>::class;
    }

<?php if ([] === $fields) { ?>
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
<?php } else { ?>
    public function configureFields(string $pageName): iterable
    {
        return [
            // a fieldset groups the fields below it in forms and on the detail page
            // FormField::addFieldset('Details'),
<?php foreach ($fields as $field) { ?>
            <?= $field; ?>,
<?php } ?>
        ];
    }
<?php } ?>
}
