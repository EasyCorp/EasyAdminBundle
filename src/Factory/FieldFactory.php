<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormFieldsetType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormRowType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminTabType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnClose;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnGroupClose;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnGroupOpen;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnOpen;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FieldFactory
{
    private static array $doctrineTypeToFieldFqcn = [
        Types::ARRAY => ArrayField::class,
        Types::BIGINT => TextField::class,
        Types::BINARY => TextareaField::class,
        Types::BLOB => TextareaField::class,
        Types::BOOLEAN => BooleanField::class,
        Types::DATE_MUTABLE => DateField::class,
        Types::DATE_IMMUTABLE => DateField::class,
        Types::DATEINTERVAL => TextField::class,
        Types::DATETIME_MUTABLE => DateTimeField::class,
        Types::DATETIME_IMMUTABLE => DateTimeField::class,
        Types::DATETIMETZ_MUTABLE => DateTimeField::class,
        Types::DATETIMETZ_IMMUTABLE => DateTimeField::class,
        Types::DECIMAL => NumberField::class,
        Types::FLOAT => NumberField::class,
        Types::GUID => TextField::class,
        Types::INTEGER => IntegerField::class,
        Types::JSON => TextField::class,
        Types::OBJECT => TextField::class,
        Types::SIMPLE_ARRAY => ArrayField::class,
        Types::SMALLINT => IntegerField::class,
        Types::STRING => TextField::class,
        Types::TEXT => TextareaField::class,
        Types::TIME_MUTABLE => TimeField::class,
        Types::TIME_IMMUTABLE => TimeField::class,
    ];

    private AdminContextProvider $adminContextProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private iterable $fieldConfigurators;

    public function __construct(AdminContextProvider $adminContextProvider, AuthorizationCheckerInterface $authorizationChecker, iterable $fieldConfigurators)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->fieldConfigurators = $fieldConfigurators;
    }

    public function processFields(EntityDto $entityDto, FieldCollection $fields): void
    {
        $this->preProcessFields($fields, $entityDto);

        $context = $this->adminContextProvider->getContext();
        $currentPage = $context->getCrud()->getCurrentPage();

        $isDetailOrIndex = \in_array($currentPage, [Crud::PAGE_INDEX, Crud::PAGE_DETAIL], true);
        foreach ($fields as $fieldDto) {
            if ((null !== $currentPage && false === $fieldDto->isDisplayedOn($currentPage))
                || false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_FIELD, $fieldDto)) {
                $fields->unset($fieldDto);

                continue;
            }

            // "form rows" only make sense in pages that contain forms
            if ($isDetailOrIndex && EaFormRowType::class === $fieldDto->getFormType()) {
                $fields->unset($fieldDto);

                continue;
            }

            // when creating new entities with "useEntryCrudForm" on an edit page we must
            // explicitly check for the "new" page because $currentPage will be "edit"
            if ((null === $entityDto->getInstance()) && !$fieldDto->isDisplayedOn(Crud::PAGE_NEW)) {
                $fields->unset($fieldDto);

                continue;
            }

            foreach ($this->fieldConfigurators as $configurator) {
                if (!$configurator->supports($fieldDto, $entityDto)) {
                    continue;
                }

                $configurator->configure($fieldDto, $entityDto, $context);
            }

            // check again if the field is displayed because this can change in the configurators
            if (null !== $currentPage && false === $fieldDto->isDisplayedOn($currentPage)) {
                $fields->unset($fieldDto);
                continue;
            }

            foreach ($fieldDto->getFormThemes() as $formThemePath) {
                $context?->getCrud()?->addFormTheme($formThemePath);
            }

            $fields->set($fieldDto);
        }

        $isPageWhereTabsAreVisible = \in_array($currentPage, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW], true);
        if ($isPageWhereTabsAreVisible) {
            $this->checkOrphanTabFields($fields, $context);
        }

        $entityDto->setFields($fields);
    }

    private function preProcessFields(FieldCollection $fields, EntityDto $entityDto): void
    {
        if ($fields->isEmpty()) {
            return;
        }

        $this->fixOrphanFieldsetFields($fields);

        $currentPage = $this->adminContextProvider->getContext()->getCrud()->getCurrentPage();
        if (\in_array($currentPage, [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            $this->fixFormColumns($fields);
        }

        foreach ($fields as $fieldDto) {
            if (Field::class !== $fieldDto->getFieldFqcn()) {
                continue;
            }

            // this is a virtual field, so we can't autoconfigure it
            if (!$entityDto->hasProperty($fieldDto->getProperty())) {
                continue;
            }

            if ($fieldDto->getProperty() === $entityDto->getPrimaryKeyName()) {
                $guessedFieldFqcn = IdField::class;
            } else {
                $doctrinePropertyType = $entityDto->getPropertyMetadata($fieldDto->getProperty())->get('type');
                $guessedFieldFqcn = self::$doctrineTypeToFieldFqcn[$doctrinePropertyType] ?? null;

                if (null === $guessedFieldFqcn) {
                    throw new \RuntimeException(sprintf('The Doctrine type of the "%s" field is "%s", which is not supported by EasyAdmin. For Doctrine\'s Custom Mapping Types have a look at EasyAdmin\'s field docs.', $fieldDto->getProperty(), $doctrinePropertyType));
                }
            }

            $fields->set($this->transformField($fieldDto, $guessedFieldFqcn));
        }
    }

    // transforms a generic Field class into a specific <type>Field class (e.g. DateTimeField)
    private function transformField(FieldDto $fieldDto, string $newFieldFqcn): FieldDto
    {
        /** @var FieldDto $newField */
        $newField = $newFieldFqcn::new($fieldDto->getProperty())->getAsDto();
        $newField->setUniqueId($fieldDto->getUniqueId());

        $newField->setFieldFqcn($newFieldFqcn);
        $newField->setDisplayedOn($fieldDto->getDisplayedOn());
        $newField->setValue($fieldDto->getValue());
        $newField->setFormattedValue($fieldDto->getFormattedValue());
        $newField->setCssClass(trim($newField->getCssClass().' '.$fieldDto->getCssClass()));
        $newField->setColumns($fieldDto->getColumns());
        $newField->setTranslationParameters($fieldDto->getTranslationParameters());
        $newField->setAssets($newField->getAssets()->mergeWith($fieldDto->getAssets()));
        foreach ($fieldDto->getFormThemes() as $formThemePath) {
            $newField->addFormTheme($formThemePath);
        }

        $customFormTypeOptions = $fieldDto->getFormTypeOptions();
        $defaultFormTypeOptions = $newField->getFormTypeOptions();
        $newField->setFormTypeOptions(array_merge($defaultFormTypeOptions, $customFormTypeOptions));

        $customFieldOptions = $fieldDto->getCustomOptions()->all();
        $defaultFieldOptions = $newField->getCustomOptions()->all();
        $mergedFieldOptions = array_merge($defaultFieldOptions, $customFieldOptions);
        $newField->setCustomOptions($mergedFieldOptions);

        if (null !== $fieldDto->getLabel()) {
            $newField->setLabel($fieldDto->getLabel());
        }

        if (null !== $fieldDto->isVirtual()) {
            $newField->setVirtual($fieldDto->isVirtual());
        }

        if (null !== $fieldDto->getTextAlign()) {
            $newField->setTextAlign($fieldDto->getTextAlign());
        }

        if (null !== $fieldDto->isSortable()) {
            $newField->setSortable($fieldDto->isSortable());
        }

        if (null !== $fieldDto->getPermission()) {
            $newField->setPermission($fieldDto->getPermission());
        }

        if (null !== $fieldDto->getHelp()) {
            $newField->setHelp($fieldDto->getHelp());
        }

        if (null !== $fieldDto->getFormType()) {
            $newField->setFormType($fieldDto->getFormType());
        }

        // don't copy the template name and path from the original Field class
        // (because they are just 'crud/field/text' and ' @EasyAdmin/crud/field/text.html.twig')
        // and use the template name/path from the new specific field (e.g. 'crud/field/datetime')

        return $newField;
    }

    /**
     * When rendering fields using tabs, all fields must belong to some tab.
     */
    private function checkOrphanTabFields(FieldCollection $fields, AdminContext $context): void
    {
        $hasTabs = false;
        $isTabField = static fn (FieldDto $fieldDto) => EasyAdminTabType::class === $fieldDto->getFormType();
        $isFormField = static fn (FieldDto $fieldDto) => FormField::class === $fieldDto->getFieldFqcn();

        foreach ($fields as $fieldDto) {
            if ($isTabField($fieldDto)) {
                $hasTabs = true;
                break;
            }
        }

        if (!$hasTabs || $isTabField($fields->first())) {
            return;
        }

        $orphanFieldNames = [];
        foreach ($fields as $field) {
            if ($isTabField($field)) {
                break;
            }

            if ($isFormField($field)) {
                continue;
            }

            $orphanFieldNames[] = $field->getProperty();
        }

        throw new \RuntimeException(sprintf('The "%s" page of "%s" uses tabs to display its fields, but the following fields don\'t belong to any tab: %s. Use "FormField::addTab(\'...\')" to add a tab before those fields.', $context->getCrud()->getCurrentPage(), $context->getCrud()->getControllerFqcn(), implode(', ', $orphanFieldNames)));
    }

    /*
     * This is needed to handle this edge-case: the list of fields include one or more form fieldsets,
     * but the first fields of the list don't belong to any fieldset. We must create an automatic empty
     * form fieldset for those "orphaned fields" so they are displayed as expected.
     */
    private function fixOrphanFieldsetFields(FieldCollection $fields): void
    {
        $formUsesFieldsets = false;
        /** @var FieldDto $fieldDto */
        foreach ($fields as $fieldDto) {
            if (EaFormFieldsetType::class === $fieldDto->getFormType()) {
                $formUsesFieldsets = true;
                break;
            }
        }

        $firstFieldIsAFieldsetOrTab = $fields->first()?->isFormDecorationField();
        if ($formUsesFieldsets && !$firstFieldIsAFieldsetOrTab) {
            $fields->prepend(FormField::addFieldset()->getAsDto());
        }
    }

    /*
     * This is used to add some special form types that will later simplify
     * the rendering of a form that uses columns.
     * If the user configures this:
     *   FormField::addColumn()
     *   Field 1
     *   FormField::addColumn()
     *   Field 2
     *   Field 3
     * This method creates the following fields:
     *   FormField::openColumnGroup()
     *   FormField::openColumn()
     *   Field 1
     *   FormField::closeColumn()
     *   FormField::openColumn()
     *   Field 2
     *   Field 3
     *   FormField::closeColumn()
     *   FormField::closeColumnGroup()
     */
    private function fixFormColumns(FieldCollection $fields): void
    {
        $formUsesColumns = false;
        $formUsesTabs = false;
        foreach ($fields as $fieldDto) {
            if ($fieldDto->isFormColumn()) {
                $formUsesColumns = true;
                continue;
            }

            if ($fieldDto->isFormTab()) {
                $formUsesTabs = true;
            }
        }

        if (false === $formUsesColumns) {
            return;
        }

        // tabs can't be defined inside columns, but columns can be defined inside tabs:
        // Not allowed: ['column', 'tab', 'field', 'tab', 'field', ...]
        // Allowed:     ['tab', 'column', 'field', 'column', 'field', 'tab', ...]
        $theFirstFieldWhichIsATabOrColumn = null;
        foreach ($fields as $fieldDto) {
            if (!$fieldDto->isFormColumn() && !$fieldDto->isFormTab()) {
                continue;
            }

            if (null === $theFirstFieldWhichIsATabOrColumn) {
                $theFirstFieldWhichIsATabOrColumn = $fieldDto;
                continue;
            }

            if ($theFirstFieldWhichIsATabOrColumn->isFormColumn() && $fieldDto->isFormTab()) {
                throw new \InvalidArgumentException(sprintf('When using form columns, you can\'t define tabs inside columns (but you can define columns inside tabs). Move the tab "%s" outside any column.', $fieldDto->getLabel()));
            }
        }

        $aFormColumnIsOpen = false;
        $aFormTabIsOpen = false;
        $isFirstFormColumn = true;

        /** @var FieldDto $fieldDto */
        foreach ($fields as $fieldDto) {
            if ($formUsesColumns && !($aFormColumnIsOpen || $aFormTabIsOpen) && !$fieldDto->isFormDecorationField()) {
                throw new \InvalidArgumentException(sprintf('When using form columns, all fields must be rendered inside a column. However, your field "%s" does not belong to any column. Move it under a form column or create a new form column before it.', $fieldDto->getProperty()));
            }

            if ($fieldDto->isFormTab()) {
                $aFormTabIsOpen = true;
            }

            if ($fieldDto->isFormColumn()) {
                $formUsesColumns = true;

                if ($isFirstFormColumn) {
                    $fields->insertBefore($this->createFieldDtoForColumnGroupOpen(), $fieldDto);
                    $isFirstFormColumn = false;
                }

                if ($aFormColumnIsOpen) {
                    $fields->insertBefore($this->createFieldDtoForColumnClose(), $fieldDto);
                }

                $aFormColumnIsOpen = true;
            }

            if ($fieldDto->isFormTab() && $aFormColumnIsOpen) {
                $fields->insertBefore($this->createFieldDtoForColumnClose(), $fieldDto);
                $fields->insertBefore($this->createFieldDtoForColumnGroupClose(), $fieldDto);
                $aFormColumnIsOpen = false;
            }
        }

        if ($formUsesColumns && $aFormColumnIsOpen) {
            $fields->add($this->createFieldDtoForColumnClose());
            $fields->add($this->createFieldDtoForColumnGroupClose());
        }
    }

    private function createFieldDtoForColumnGroupOpen(): FieldDto
    {
        return Field::new(sprintf('ea_form_column_group_open_%s', Ulid::generate()))
            ->setFormType(EaFormColumnGroupOpen::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createFieldDtoForColumnGroupClose(): FieldDto
    {
        return Field::new(sprintf('ea_form_column_group_close_%s', Ulid::generate()))
            ->setFormType(EaFormColumnGroupClose::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createFieldDtoForColumnClose(): FieldDto
    {
        return Field::new(sprintf('ea_form_column_close_%s', Ulid::generate()))
            ->setFormType(EaFormColumnClose::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }
}
