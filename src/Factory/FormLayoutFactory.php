<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldLayoutDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminTabType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnGroupCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnGroupOpenType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormFieldsetCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormFieldsetOpenType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabListType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneGroupCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneGroupOpenType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneOpenType;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Ulid;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
final class FormLayoutFactory
{
    public function __construct()
    {
    }

    public function createLayout(FieldCollection $fields, string $pageName): FieldCollection
    {
        // the index page has no layout elements such as tabs, form columns or fieldsets
        if (Crud::PAGE_INDEX === $pageName) {
            return $fields;
        }

        $this->fixOrphanFieldsetFields($fields);
        $this->fixFormColumns($fields);
        $this->checkOrphanTabFields($fields);

        return $fields;
    }

    /*
     * This is needed to handle this edge-case: the list of fields include one or more form fieldsets,
     * but the first fields of the list don't belong to any fieldset. We must create an automatic empty
     * form fieldset for those "orphaned fields" so they are displayed as expected.
     */
    private function fixOrphanFieldsetFields(FieldCollection $fields): void
    {
        $firstFieldAfterFormTabOrColumn = null;
        $weAreInAFormTabOrColumn = false;
        $formUsesFieldsets = false;
        $insertFieldsetBeforeTheseFields = [];
        /** @var FieldDto $fieldDto */
        foreach ($fields as $fieldDto) {
            if ($fieldDto->isFormTab() || $fieldDto->isFormColumn()) {
                $firstFieldAfterFormTabOrColumn = null;
                $weAreInAFormTabOrColumn = true;
                continue;
            }

            if ($weAreInAFormTabOrColumn && null === $firstFieldAfterFormTabOrColumn && !$fieldDto->isFormLayoutField()) {
                $firstFieldAfterFormTabOrColumn = $fieldDto;
                continue;
            }

            if ($fieldDto->isFormFieldset()) {
                $formUsesFieldsets = true;

                if (null !== $firstFieldAfterFormTabOrColumn) {
                    $insertFieldsetBeforeTheseFields[] = $firstFieldAfterFormTabOrColumn;
                }
            }
        }

        // edge-case: the form doesn't use tabs or columns and some fields are
        // outside of fieldsets; we must add a fieldset in this case too
        $firstFieldIsALayoutField = $fields->first()?->isFormLayoutField();
        if ($formUsesFieldsets && !$firstFieldIsALayoutField) {
            $insertFieldsetBeforeTheseFields[] = $fields->first();
        }

        foreach ($insertFieldsetBeforeTheseFields as $fieldDto) {
            $fields->insertBefore($this->createFieldsetOpenField(), $fieldDto);
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
        //$this->fixOrphanFieldsetFields($fields);

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
        $tabsWithoutLabelCounter = 0;

        $slugger = new AsciiSlugger();
        $tabs = [];
        /** @var FieldDto $fieldDto */
        foreach ($fields as $fieldDto) {
            if ($formUsesColumns && !($aFormColumnIsOpen || $aFormTabIsOpen) && !$fieldDto->isFormLayoutField()) {
                throw new \InvalidArgumentException(sprintf('When using form columns, all fields must be rendered inside a column. However, your field "%s" does not belong to any column. Move it under a form column or create a new form column before it.', $fieldDto->getProperty()));
            }

            if ($fieldDto->isFormTab()) {
                $isTabActive = 0 === \count($tabs);
                $tabId = sprintf('tab-%s', $fieldDto->getLabel() ? $slugger->slug($fieldDto->getLabel())->lower()->toString() : ++$tabsWithoutLabelCounter);
                $fieldDto->setCustomOption(FormField::OPTION_TAB_ID, $tabId);
                $fieldDto->setCustomOption(FormField::OPTION_TAB_IS_ACTIVE, $isTabActive);

                $fieldDto->setFormTypeOptions([
                    'ea_tab_id' => $tabId,
                    'ea_css_class' => $fieldDto->getCssClass(),
                    'ea_help' => $fieldDto->getHelp(),
                    'ea_tab_is_active' => $isTabActive,
                ]);

                $tabs[$tabId] = $fieldDto;

                if ($aFormFieldsetIsOpen) {
                    $fields->insertBefore($this->createFieldsetCloseField(), $fieldDto);
                    $aFormFieldsetIsOpen = false;
                }

                if ($aFormColumnIsOpen) {
                    $fields->insertBefore($this->createColumnCloseField(), $fieldDto);
                    $fields->insertBefore($this->createColumnGroupCloseField(), $fieldDto);
                    $aFormColumnIsOpen = false;
                }

                if ($aFormTabIsOpen) {
                    $fields->insertBefore($this->createTabPaneCloseField(), $fieldDto);
                }

                $aFormTabIsOpen = true;
            }

            if ($fieldDto->isFormFieldset()) {
                if ($aFormFieldsetIsOpen) {
                    $fields->insertBefore($this->createFieldsetCloseField(), $fieldDto);
                }

                $aFormFieldsetIsOpen = true;

                $fieldDto->setFormTypeOptions([
                    'ea_css_class' => $fieldDto->getCssClass(),
                    'ea_icon' => $fieldDto->getCustomOption('icon'),
                    'ea_help' => $fieldDto->getHelp(),
                    'ea_is_collapsible' => $fieldDto->getCustomOption(FormField::OPTION_COLLAPSIBLE),
                    'ea_is_collapsed' => $fieldDto->getCustomOption(FormField::OPTION_COLLAPSED),
                ]);
            }

            if ($fieldDto->isFormColumn()) {
                $formUsesColumns = true;

                if ($isFirstFormColumn) {
                    $fields->insertBefore($this->createColumnGroupOpenField(), $fieldDto);
                    $isFirstFormColumn = false;
                }

                if ($aFormFieldsetIsOpen) {
                    $fields->insertBefore($this->createFieldsetCloseField(), $fieldDto);
                    $aFormFieldsetIsOpen = false;
                }

                if ($aFormColumnIsOpen) {
                    $fields->insertBefore($this->createColumnCloseField(), $fieldDto);
                }

                $aFormColumnIsOpen = true;
            }

            if ($aFormColumnIsOpen) {
                // this is needed because fields inside columns look better when they take the
                // entire width available; users can override this by setting custom CSS classes
                $fieldDto->setDefaultColumns('');
            }
        }

        if ($aFormFieldsetIsOpen) {
            $fields->add($this->createFieldsetCloseField());
        }

        if ($aFormColumnIsOpen) {
            $fields->add($this->createColumnCloseField());
            $fields->add($this->createColumnGroupCloseField());
        }

        if ($formUsesTabs) {
            $fields->add($this->createTabPaneCloseField());
            $fields->add($this->createTabPaneGroupCloseField());
            $fields->prepend($this->createTabPaneGroupOpenField());
            $fields->prepend($this->createTabListField($tabs));
        }

        dump("AFTER");
        foreach ($fields as $field) {
            dump($field->getFormType());
        }
    }

    private function createColumnGroupOpenField(): FieldDto
    {
        return Field::new(sprintf('ea_form_column_group_open_%s', Ulid::generate()))
            ->setFormType(EaFormColumnGroupOpenType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createColumnGroupCloseField(): FieldDto
    {
        return Field::new(sprintf('ea_form_column_group_close_%s', Ulid::generate()))
            ->setFormType(EaFormColumnGroupCloseType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createColumnCloseField(): FieldDto
    {
        return Field::new(sprintf('ea_form_column_close_%s', Ulid::generate()))
            ->setFormType(EaFormColumnCloseType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createFieldsetOpenField(): FieldDto
    {
        return FormField::addFieldset()->getAsDto();
    }

    private function createFieldsetCloseField(): FieldDto
    {
        return Field::new(sprintf('ea_form_fieldset_close_%s', Ulid::generate()))
            ->setFormType(EaFormFieldsetCloseType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createTabPaneGroupOpenField(): FieldDto
    {
        return Field::new(sprintf('ea_form_tabpane_group_open_%s', Ulid::generate()))
            ->setFormType(EaFormTabPaneGroupOpenType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createTabPaneGroupCloseField(): FieldDto
    {
        return Field::new(sprintf('ea_form_tabpane_group_close_%s', Ulid::generate()))
            ->setFormType(EaFormTabPaneGroupCloseType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->getAsDto();
    }

    private function createTabListField(array $tabs): FieldDto
    {
        return Field::new(sprintf('ea_form_tablist_%s', Ulid::generate()))
            ->setFormType(EaFormTabListType::class)
            ->setFormTypeOptions(['mapped' => false, 'required' => false])
            ->setCustomOption('tabs', $tabs)
            ->getAsDto();
    }

    private function createTabPaneCloseField(): FieldDto
    {
        return Field::new(sprintf('ea_form_tabpane_close_%s', Ulid::generate()))
            ->setFormType(EaFormTabPaneCloseType::class)
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
