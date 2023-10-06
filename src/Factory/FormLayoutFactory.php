<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldLayoutDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminTabType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnClose;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnGroupClose;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnGroupOpen;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormFieldsetClose;
use Symfony\Component\Uid\Ulid;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
final class FormLayoutFactory
{
    private function __construct()
    {
    }

    public function createLayout(FieldCollection $fields, string $pageName): void
    {
        // the index page has no layout elements such as tabs, form columns or fieldsets
        if (Crud::PAGE_INDEX === $pageName) {
            return;
        }

        $this->fixOrphanFieldsetFields($fields);
        $this->fixFormColumns($fields);

        $this->checkOrphanTabFields($fields);
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
            if ($fieldDto->isFormFieldset()) {
                $formUsesFieldsets = true;
                break;
            }
        }

        $firstFieldIsALayoutField = $fields->first()?->isFormLayoutField();
        if ($formUsesFieldsets && !$firstFieldIsALayoutField) {
            // if the first field is not a layout field, then it's a regular field;
            // but, since the form uses fieldset, this field (and possibly others)
            // don't belong to any fieldset. To avoid design issues, add a fieldset to them
            $fields->prepend(FormField::addFieldset()->getAsDto());
        }
    }

    /*
     * This is used to add some special form types that will later simplify
     * the rendering of a form that uses columns.
     * Fir example, if the user configures this:
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
     *
     * See tests for many other examples of complex layouts
     */
    private function fixFormColumns(FieldCollection $fields): void
    {
        $this->fixOrphanFieldsetFields($fields);

        dump("BEFORE");
        foreach ($fields as $field) {
            dump($field->getFormType());
        }

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
        $aFormFieldsetIsOpen = false;
        $isFirstFormColumn = true;

        /** @var FieldDto $fieldDto */
        foreach ($fields as $fieldDto) {
            if ($formUsesColumns && !($aFormColumnIsOpen || $aFormTabIsOpen) && !$fieldDto->isFormLayoutField()) {
                throw new \InvalidArgumentException(sprintf('When using form columns, all fields must be rendered inside a column. However, your field "%s" does not belong to any column. Move it under a form column or create a new form column before it.', $fieldDto->getProperty()));
            }

            if ($fieldDto->isFormTab()) {
                $aFormTabIsOpen = true;

                if ($aFormFieldsetIsOpen) {
                    $fields->insertBefore($this->createFieldDtoForFieldsetClose(), $fieldDto);
                    $aFormFieldsetIsOpen = false;
                }
            }

            if ($fieldDto->isFormFieldset()) {
                if ($aFormFieldsetIsOpen) {
                    $fields->insertBefore($this->createFieldDtoForFieldsetClose(), $fieldDto);
                }

                $aFormFieldsetIsOpen = true;
            }

            if ($fieldDto->isFormColumn()) {
                $formUsesColumns = true;

                if ($isFirstFormColumn) {
                    $fields->insertBefore($this->createFieldDtoForColumnGroupOpen(), $fieldDto);
                    $isFirstFormColumn = false;
                }

                if ($aFormFieldsetIsOpen) {
                    $fields->insertBefore($this->createFieldDtoForFieldsetClose(), $fieldDto);
                    $aFormFieldsetIsOpen = false;
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

            if ($aFormColumnIsOpen) {
                // this is needed because fields inside columns look better when they take the
                // entire width available; users can override this by setting custom CSS classes
                $fieldDto->setDefaultColumns('');
            }
        }

        if ($aFormFieldsetIsOpen) {
            $fields->add($this->createFieldDtoForFieldsetClose());
        }

        if ($aFormColumnIsOpen) {
            $fields->add($this->createFieldDtoForColumnClose());
            $fields->add($this->createFieldDtoForColumnGroupClose());
        }

        dump("AFTER");
        foreach ($fields as $field) {
            dump($field->getFormType());
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

    private function createFieldDtoForFieldsetClose(): FieldDto
    {
        return Field::new(sprintf('ea_form_fieldset_close_%s', Ulid::generate()))
            ->setFormType(EaFormFieldsetClose::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    /**
     * When rendering fields using tabs, all fields must belong to some tab.
     */
    private function checkOrphanTabFields(FieldCollection $fields): void
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

        throw new \RuntimeException(sprintf('The "%s" page of "%s" uses tabs to display its fields, but the following fields don\'t belong to any tab: %s. Use "FormField::addTab(\'...\')" to add a tab before those fields.', '$context->getCrud()->getCurrentPage()', '$context->getCrud()->getControllerFqcn()', implode(', ', $orphanFieldNames)));
    }

    public static function createFromFieldDtos(FieldCollection|null $fieldDtos): FieldLayoutDto
    {
        if (null === $fieldDtos) {
            return new FieldLayoutDto();
        }

        $hasTabs = false;
        foreach ($fieldDtos as $fieldDto) {
            if ($fieldDto->isFormTab()) {
                $hasTabs = true;
                break;
            }
        }

        $tabs = [];
        $fields = [];
        $currentTab = null;
        /** @var FieldDto $fieldDto */
        foreach ($fieldDtos as $fieldDto) {
            if ($fieldDto->isFormTab()) {
                $currentTab = $fieldDto;
                $tabs[$fieldDto->getUniqueId()] = $fieldDto;
            } else {
                if ($hasTabs) {
                    $fields[$currentTab->getUniqueId()][] = $fieldDto;
                } else {
                    $fields[] = $fieldDto;
                }
            }
        }

        return new FieldLayoutDto($fields, $tabs);
    }
}
