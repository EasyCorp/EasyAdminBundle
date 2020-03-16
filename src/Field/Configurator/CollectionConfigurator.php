<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use function Symfony\Component\String\u;

final class CollectionConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldInterface $field, EntityDto $entityDto): bool
    {
        return $field instanceof CollectionField;
    }

    public function configure(FieldInterface $field, EntityDto $entityDto, string $action): void
    {
        if (null !== $entryTypeFqcn = $field->getCustomOptions()->get(CollectionField::OPTION_ENTRY_TYPE)) {
            $field->setFormTypeOption('entry_type', $entryTypeFqcn);
        }

        $autocompletableFormTypes = [CountryType::class, CurrencyType::class, LanguageType::class, LocaleType::class, TimezoneType::class];
        if (\in_array($entryTypeFqcn, $autocompletableFormTypes, true)) {
            $field->setFormTypeOption('entry_options.attr.data-widget', 'select2');
        }

        $field->setFormTypeOption('allow_add', $field->getCustomOptions()->get(CollectionField::OPTION_ALLOW_ADD));
        $field->setFormTypeOption('allow_delete', $field->getCustomOptions()->get(CollectionField::OPTION_ALLOW_DELETE));

        $field->setFormTypeOptionIfNotSet('delete_empty', true);

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

        $field->setFormattedValue($this->formatCollection($field, $action));
    }

    private function formatCollection(FieldInterface $field, string $action)
    {
        $doctrineMetadata = $field->getDoctrineMetadata();
        if ('array' !== $doctrineMetadata->get('type') && !$field->getValue() instanceof PersistentCollection) {
            return $this->countNumElements($field->getValue());
        }

        $collectionItemsAsText = [];
        foreach ($field->getValue() as $item) {
            if (!\is_string($item) && !method_exists($item, '__toString')) {
                return $this->countNumElements($field->getValue());
            }

            $collectionItemsAsText[] = (string) $item;
        }

        return u(', ')->join($collectionItemsAsText)->truncate(Action::DETAIL === $action ? 512 : 32, '…');
    }

    private function countNumElements($collection): int
    {
        if (null === $collection) {
            return 0;
        }

        if (\is_array($collection) || $collection instanceof \Countable) {
            return \count($collection);
        }

        if ($collection instanceof \Traversable) {
            return iterator_count($collection);
        }

        return 0;
    }
}
