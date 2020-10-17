<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormPanelType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormTabType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormGroupType;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FieldFactory
{
    private static $doctrineTypeToFieldFqcn = [
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

    private $adminContextProvider;
    private $authorizationChecker;
    private $fieldConfigurators;

    public function __construct(AdminContextProvider $adminContextProvider, AuthorizationCheckerInterface $authorizationChecker, iterable $fieldConfigurators)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->fieldConfigurators = $fieldConfigurators;
    }

    /**
     * Different steps of the fields processing:
     * 1) Pre-process: for each generic Field, try to determine its specialized EasyAdmin Field.
     * 2) Remove the unwanted fields (for display or security reasons).
     *    The removal process is in cascade (eg: all child-fields of a removed tab are also removed).
     * 3) In the cases of non-index page, do:
     *    3.1) Create the missing decorators to complete the hierarchy of fields
     *    3.2) Remove the decorators having no children (because these children were removed at step 2)
     *    3.3) For each normal field, store its parent panel, tab and group
     *  4) Configure each field with the supported configurators.
     *  5) Store 'flat fields' and 'hierarchized fields' in the EntityDto
     */
    public function processFields(EntityDto $entityDto, FieldCollection $fields): void
    {
        $context = $this->adminContextProvider->getContext();
        $currentPage = $context->getCrud()->getCurrentPage();
        $isIndexPage = Crud::PAGE_INDEX === $currentPage;
        $nextDecorator = null;
        $panels = $tabs = $groups = $normalFields = [];

        $this->preProcessFields($fields, $entityDto);

        foreach ($fields as $fieldName => $fieldDto) {
            // On index page, decorators are removed
            if ($isIndexPage && $fieldDto->isDecorator()) {
                continue;
            }

            $formType = $fieldDto->getFormType();

            // 3 cases to remove a field: 1) cascading removal, 2) display restriction, 3) security restriction
            if (true === $this->isToRemoveField($fieldDto, $nextDecorator)
                || (null !== $currentPage && false === $fieldDto->isDisplayedOn($currentPage))
                || false === $this->authorizationChecker->isGranted(Permission::EA_VIEW_FIELD, $fieldDto)) {

                if (!$nextDecorator && $fieldDto->isDecorator()) {
                    $nextDecorator = $formType;
                }

                continue;
            }

            if ($fieldDto->isDecorator()) {
                $this->removePreviousDecoratorsIfNoChildren($formType, $panels, $tabs, $groups, $normalFields);
            }

            if (EaFormPanelType::class === $formType) {
                unset($tabs); $tabs = [];
                $panels[$fieldName] = ['field' => $fieldDto, 'tabs' => &$tabs];
                continue;
            }

            if (EaFormTabType::class === $formType) {
                unset($groups); $groups = [];
                $tabs[$fieldName] = ['field' => $fieldDto, 'groups' => &$groups];
                continue;
            }

            // At this point we should have a tab, so create one.
            if (!$isIndexPage && [] === $tabs) {
                unset($groups); $groups = [];
                $new = FormField::addTab('General')->getAsDto();
                $tabs[$new->getProperty()] = ['field' => $new, 'groups' => &$groups];
            }

            if (EaFormGroupType::class === $formType) {
                unset($normalFields); $normalFields = [];
                $groups[$fieldName] = ['field' => $fieldDto, 'fields' => &$normalFields];
                continue;
            }

            // At this point we should have a group, so create one.
            if (!$isIndexPage && [] === $groups) {
                unset($normalFields); $normalFields = [];
                $new = FormField::addGroup()->getAsDto();
                $groups[$new->getProperty()] = ['field' => $new, 'fields' => &$normalFields];
            }

            if (!$isIndexPage) {
                $fieldDto->setDecorators(end($panels)['field'], end($tabs)['field'], end($groups)['field']);
            }

            $normalFields[$fieldName] = $fieldDto;
        }

        /**
         * From $panels or $normalFields:
         *  1) browse all fields in order
         *  2) apply the configurators on each
         *  3) then push each one in a flat FieldCollection
         */
        $fields = FieldCollection::new([]);

        // Wow! Super anonymous recursive function! :-)
        ($flatten = function ($e) use (&$flatten, $entityDto, $fields) {
            if ($e instanceof FieldDto) {
                $this->configureField($e, $entityDto);
                $fields->set($e);
            }
            elseif (isset($e['field'])) {
                $flatten($e['field']);
                $flatten($e['tabs'] ?? $e['groups'] ?? $e['fields']);
            }
            else {
                array_map($flatten, $e);
            }
        })($isIndexPage ? $normalFields : $panels);

        $entityDto->setFields($fields);
        $entityDto->setHierarchizedFields($panels);
    }

    private function preProcessFields(FieldCollection $fields, EntityDto $entityDto): void
    {
        if ($fields->isEmpty()) {
            return;
        }

        // The first field is necessarily a panel! If not the case, we create one.
        if (EaFormPanelType::class !== $fields->first()->getFormType()) {
            $fields->prepend(FormField::addPanel()->getAsDto());
        }

        foreach ($fields as $fieldName => $fieldDto) {
            if (Field::class !== $fieldDto->getFieldFqcn()) {
                continue;
            }

            // this is a virtual field, so we can't autoconfigure it
            if (!$entityDto->hasProperty($fieldDto->getProperty())) {
                continue;
            }

            if ($fieldName === $entityDto->getPrimaryKeyName()) {
                $guessedFieldFqcn = IdField::class;
            } else {
                $doctrinePropertyType = $entityDto->getPropertyMetadata($fieldName)->get('type');
                $guessedFieldFqcn = self::$doctrineTypeToFieldFqcn[$doctrinePropertyType] ?? null;

                if (null === $guessedFieldFqcn) {
                    throw new \RuntimeException(sprintf('The Doctrine type of the "%s" field is "%s", which is not supported by EasyAdmin yet.', $fieldName, $doctrinePropertyType));
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

        $newField->setFieldFqcn($newFieldFqcn);
        $newField->setDisplayedOn($fieldDto->getDisplayedOn());
        $newField->setValue($fieldDto->getValue());
        $newField->setFormattedValue($fieldDto->getFormattedValue());
        $newField->setCssClass($fieldDto->getCssClass());
        $newField->setTranslationParameters($fieldDto->getTranslationParameters());
        $newField->setAssets($newField->getAssets()->mergeWith($fieldDto->getAssets()));

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

    private function configureField(FieldDto $fieldDto, EntityDto $entityDto)
    {
        $context = $this->adminContextProvider->getContext();

        foreach ($this->fieldConfigurators as $configurator) {
            if (!$configurator->supports($fieldDto, $entityDto)) {
                continue;
            }

            $configurator->configure($fieldDto, $entityDto, $context);
        }
    }

    /**
     * When a decorator is removed, we also have to remove all of its children (decorators and fields).
     * This function determines if $field should also be removed.
     * If so, we return true.
     * Otherwise, we return false and set $nextDecorator to null (to stop cascading removal).
     */
    private function isToRemoveField(FieldDto $field, ?string & $nextDecorator): bool
    {
        if (! $nextDecorator) { return false; }

        $formType = $field->getFormType();

        if (EaFormPanelType::class === $nextDecorator) {
            if (EaFormPanelType::class === $formType) {
                $nextDecorator = null;
                return false;
            }
            return true;
        }
        elseif (EaFormTabType::class === $nextDecorator) {
            if (in_array($formType, [EaFormTabType::class, EaFormPanelType::class])) {
                $nextDecorator = null;
                return false;
            }
            return true;
        }
        elseif (EaFormGroupType::class === $nextDecorator) {
            if (in_array($formType, [EaFormGroupType::class, EaFormTabType::class, EaFormPanelType::class])) {
                $nextDecorator = null;
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * During the fields processing, some decorators have potentially no children
     * (because their child-fields have been removed).
     *
     * So before to add a new decorator, we have to remove the previous empty decorators of same level and lower.
     * When we add a group, we have to remove the previous group if empty.
     * When we add a tab, we have to remove the previous group and tab if empty.
     * And when we add a panel, we have to remove the previous group, tab and panel if empty.
     */
    private function removePreviousDecoratorsIfNoChildren(
        string $newDecorator,
        array & $panels,
        array & $tabs,
        array & $groups,
        array $normalFields
    ): void {
        // Previous group has no children, so remove it
        if ([] !== $groups && [] === $normalFields) {
            array_pop($groups);
        }

        // Previous tab has no children, so remove it
        if ((EaFormPanelType::class === $newDecorator || EaFormTabType::class === $newDecorator)
            && [] !== $tabs && [] === $groups
        ) {
            array_pop($tabs);
        }

        // Previous panel has no children, so remove it
        if (EaFormPanelType::class === $newDecorator && [] !== $panels && [] === $tabs) {
            array_pop($panels);
        }
    }
}
