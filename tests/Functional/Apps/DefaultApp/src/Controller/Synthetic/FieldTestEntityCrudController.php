<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;

/**
 * CrudController for testing all field types in EasyAdmin.
 *
 * @extends AbstractCrudController<FieldTestEntity>
 */
class FieldTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FieldTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // text fields
        yield TextField::new('textField');
        yield TextareaField::new('textareaField');
        yield TextEditorField::new('textEditorField');
        yield CodeEditorField::new('codeEditorField');
        yield EmailField::new('emailField');
        yield TelephoneField::new('telephoneField');
        yield UrlField::new('urlField');
        yield SlugField::new('slugField')->setTargetFieldName('textField');

        // numeric fields
        yield IntegerField::new('integerField');
        yield NumberField::new('numberField');
        yield MoneyField::new('moneyField')->setCurrency('USD')->setStoredAsCents(true);
        yield PercentField::new('percentField');

        // dateTime fields
        yield DateField::new('dateField');
        yield TimeField::new('timeField');
        yield DateTimeField::new('dateTimeField');

        // choice fields
        yield BooleanField::new('booleanField');
        yield ChoiceField::new('choiceField')->setChoices([
            'Option A' => 'option_a',
            'Option B' => 'option_b',
            'Option C' => 'option_c',
        ]);
        yield ChoiceField::new('multipleChoiceField')->setChoices([
            'Choice 1' => 'choice1',
            'Choice 2' => 'choice2',
            'Choice 3' => 'choice3',
        ])->allowMultipleChoices();

        // collection fields
        yield ArrayField::new('arrayField');
        yield CollectionField::new('collectionField');

        // intl fields
        yield CountryField::new('countryField');
        yield LanguageField::new('languageField');
        yield LocaleField::new('localeField');
        yield TimezoneField::new('timezoneField');
        yield CurrencyField::new('currencyField');

        // media fields
        yield ImageField::new('imageField')
            ->setBasePath('uploads/')
            ->setUploadDir('public/uploads/')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
        yield AvatarField::new('avatarField');

        // special fields
        yield ColorField::new('colorField');
        yield HiddenField::new('hiddenField');

        // association fields
        yield AssociationField::new('manyToOneAssociation');
        yield AssociationField::new('manyToManyAssociation');
    }
}
