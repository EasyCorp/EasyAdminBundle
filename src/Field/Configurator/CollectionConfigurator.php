<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\ControllerFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CollectionConfigurator implements FieldConfiguratorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityFactory $entityFactory,
        private readonly ControllerFactory $controllerFactory,
        private readonly ?FieldFactory $fieldFactory = null,
    ) {
        if (null === $this->fieldFactory) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.27.0',
                'Not passing argument "$fieldFactory" to the "%s" constructor is deprecated.',
                self::class
            );
        }
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return CollectionField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (null !== $entryTypeFqcn = $field->getCustomOptions()->get(CollectionField::OPTION_ENTRY_TYPE)) {
            $field->setFormTypeOption('entry_type', $entryTypeFqcn);
        }

        $autocompletableFormTypes = [CountryType::class, CurrencyType::class, LanguageType::class, LocaleType::class, TimezoneType::class];
        if (\in_array($entryTypeFqcn, $autocompletableFormTypes, true)) {
            $field->setFormTypeOption('entry_options.attr.data-ea-widget', 'ea-autocomplete');
        }

        // the contents of this field are a collection of other fields, so it cannot be sorted
        $field->setSortable(false);

        $field->setFormTypeOptionIfNotSet('allow_add', $field->getCustomOptions()->get(CollectionField::OPTION_ALLOW_ADD));
        $field->setFormTypeOptionIfNotSet('allow_delete', $field->getCustomOptions()->get(CollectionField::OPTION_ALLOW_DELETE));
        $field->setFormTypeOptionIfNotSet('by_reference', false);
        $field->setFormTypeOptionIfNotSet('delete_empty', true);
        $field->setFormTypeOptionIfNotSet('prototype_name', '__'.u($field->getProperty())->replace('.', '_').'name__');

        // TODO: check why this label (hidden by default) is not working properly
        // (generated values are always the same for all elements)
        $field->setFormTypeOptionIfNotSet('entry_options.label', $field->getCustomOptions()->get(CollectionField::OPTION_SHOW_ENTRY_LABEL));

        // collection items range from a simple <input text> to a complex multi-field form
        // the 'entryIsComplex' setting tells if the collection item is so complex that needs a special
        // rendering not applied to simple collection items
        if (null === $field->getCustomOption(CollectionField::OPTION_ENTRY_IS_COMPLEX)) {
            $definesEntryType = null !== $entryTypeFqcn = $field->getCustomOption(CollectionField::OPTION_ENTRY_TYPE);
            $isSymfonyCoreFormType = null !== u($entryTypeFqcn ?? '')->indexOf('Symfony\Component\Form\Extension\Core\Type');
            $isComplexEntry = $definesEntryType && !$isSymfonyCoreFormType;

            $field->setCustomOption(CollectionField::OPTION_ENTRY_IS_COMPLEX, $isComplexEntry);
        }

        $field->setFormattedValue($this->formatCollection($field, $context));

        $this->configureEntryType($field, $entityDto, $context);
    }

    private function formatCollection(FieldDto $field, AdminContext $context): int|string
    {
        $doctrineMetadata = $field->getDoctrineMetadata();
        if ('array' !== $doctrineMetadata->get('type') && !$field->getValue() instanceof PersistentCollection) {
            return $this->countNumElements($field->getValue());
        }

        $collectionItemsAsText = [];
        foreach ($field->getValue() ?? [] as $item) {
            if (!\is_string($item) && !$item instanceof \Stringable) {
                return $this->countNumElements($field->getValue());
            }

            $collectionItemsAsText[] = (string) $item;
        }

        $isDetailAction = Action::DETAIL === $context->getCrud()->getCurrentAction();
        $maxLength = $field->getCustomOption(CollectionField::OPTION_MAX_LENGTH) ?? ($isDetailAction ? 512 : 32);

        return u(', ')->join($collectionItemsAsText)->truncate($maxLength, '…')->toString();
    }

    private function countNumElements(mixed $collection): int
    {
        if (null === $collection) {
            return 0;
        }

        if (is_countable($collection)) {
            return \count($collection);
        }

        if ($collection instanceof \Traversable) {
            return iterator_count($collection);
        }

        return 0;
    }

    private function configureEntryType(FieldDto $fieldDto, EntityDto $entityDto, AdminContext $context): void
    {
        if (true === $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM)) {
            if (!$entityDto->getClassMetadata()->hasAssociation($fieldDto->getProperty())) {
                throw new \RuntimeException(sprintf('The "%s" collection field of "%s" cannot use the "useEntryCrudForm()" method because it is not a Doctrine association.', $fieldDto->getProperty(), $context->getCrud()?->getControllerFqcn()));
            }

            if (null !== $fieldDto->getCustomOptions()->get(CollectionField::OPTION_ENTRY_TYPE)) {
                throw new \RuntimeException(sprintf('The "%s" collection field of "%s" can render its entries using a Symfony Form (via the "setEntryType()" method) or using an EasyAdmin CRUD Form (via the "useEntryCrudForm()" method) but you cannot use both methods at the same time. Remove one of those two methods.', $fieldDto->getProperty(), $context->getCrud()?->getControllerFqcn()));
            }

            $targetCrudControllerFqcn = $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_CRUD_CONTROLLER_FQCN)
                ?? $context->getCrudControllers()->findCrudFqcnByEntityFqcn($entityDto->getClassMetadata()->getAssociationTargetClass($fieldDto->getProperty()));

            if (null === $targetCrudControllerFqcn) {
                throw new \RuntimeException(sprintf('The "%s" collection field of "%s" wants to render its entries using an EasyAdmin CRUD form. However, no CRUD form was found related to this field. You can either create a CRUD controller for the entity "%s" or pass the CRUD controller to use as the first argument of the "useEntryCrudForm()" method.', $fieldDto->getProperty(), $context->getCrud()?->getControllerFqcn(), $entityDto->getClassMetadata()->getAssociationTargetClass($fieldDto->getProperty())));
            }
        } elseif (null === $fieldDto->getFormTypeOption('entry_type')
            && $entityDto->getClassMetadata()->hasAssociation($fieldDto->getProperty())) {
            $targetCrudControllerFqcn = $context->getCrudControllers()->findCrudFqcnByEntityFqcn($entityDto->getClassMetadata()->getAssociationTargetClass($fieldDto->getProperty()));

            if (null === $targetCrudControllerFqcn) {
                return;
            }
        } else {
            return;
        }

        $editEntityDto = $this->createEntityDto(
            $entityDto->getClassMetadata()->getAssociationTargetClass($fieldDto->getProperty()),
            $targetCrudControllerFqcn,
            Action::EDIT,
            $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_CRUD_EDIT_PAGE_NAME) ?? Crud::PAGE_EDIT,
            Crud::PAGE_EDIT,
        );
        $newEntityDto = $this->createEntityDto(
            $entityDto->getClassMetadata()->getAssociationTargetClass($fieldDto->getProperty()),
            $targetCrudControllerFqcn,
            Action::NEW,
            $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_CRUD_NEW_PAGE_NAME) ?? Crud::PAGE_NEW,
            Crud::PAGE_NEW,
        );

        $fieldDto->setFormTypeOption('entry_type', CrudFormType::class);
        $fieldDto->setFormTypeOption('entry_options.entityDto', $editEntityDto);
        try {
            $fieldDto->setFormTypeOption('prototype_options.entityDto', $newEntityDto);
        } catch (UndefinedOptionsException $exception) {
            throw new \RuntimeException(sprintf('The "%s" collection field of "%s" uses the "useEntryCrudForm()" method, which requires Symfony 6.1 or newer to work. Upgrade your Symfony version or use instead the "setEntryType()" method to render the collection entries using a Symfony form.', $fieldDto->getProperty(), $context->getCrud()?->getControllerFqcn()), 0, $exception);
        }
    }

    /**
     * @param class-string $targetEntityFqcn
     * @param class-string $targetCrudControllerFqcn
     */
    private function createEntityDto(string $targetEntityFqcn, string $targetCrudControllerFqcn, string $crudAction, string $crudControllerPageName, string $crudPageName): EntityDto
    {
        $entityDto = $this->entityFactory->create($targetEntityFqcn);

        $crudController = $this->controllerFactory->getCrudControllerInstance(
            $targetCrudControllerFqcn,
            $crudAction,
            $this->requestStack->getMainRequest()
        );

        $fields = $crudController->configureFields($crudControllerPageName);

        if (null === $this->fieldFactory) {
            $this->entityFactory->processFields($entityDto, FieldCollection::new($fields), $crudPageName);
        } else {
            $this->fieldFactory->processFields($entityDto, FieldCollection::new($fields), $crudPageName);
        }

        return $entityDto;
    }
}
