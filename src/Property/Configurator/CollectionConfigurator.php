<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Property\Configurator;

use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\CollectionProperty;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use function Symfony\Component\String\u;

final class CollectionConfigurator implements PropertyConfiguratorInterface
{
    public function supports(PropertyConfigInterface $propertyConfig, EntityDto $entityDto): bool
    {
        return $propertyConfig instanceof CollectionProperty;
    }

    public function configure(string $action, PropertyConfigInterface $propertyConfig, EntityDto $entityDto): void
    {
        if (null !== $entryTypeFqcn = $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_ENTRY_TYPE)) {
            $propertyConfig->setFormTypeOption('entry_type', $entryTypeFqcn);
        }

        if (in_array($entryTypeFqcn, [CountryType::class, CurrencyType::class, LanguageType::class, LocaleType::class, TimezoneType::class])) {
            $propertyConfig->setFormTypeOption('entry_options.attr.data-widget', 'select2');
        }

        $propertyConfig->setFormTypeOption('allow_add', $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_ALLOW_ADD));
        $propertyConfig->setFormTypeOption('allow_delete', $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_ALLOW_DELETE));

        $propertyConfig->setFormTypeOptionIfNotSet('delete_empty', true);

        // TODO: check why this label (hidden by default) is not working properly
        // (generated values are always the same for all elements)
        $propertyConfig->setFormTypeOptionIfNotSet('entry_options.label', $propertyConfig->getCustomOptions()->get(CollectionProperty::OPTION_SHOW_ENTRY_LABEL));

        // collection items range from a simple <input text> to a complex multi-field form
        // the 'entryIsComplex' setting tells if the collection item is so complex that needs a special
        // rendering not applied to simple collection items
        if (null === $propertyConfig->getCustomOption(CollectionProperty::OPTION_ENTRY_IS_COMPLEX)) {
            $definesEntryType = null !== $entryTypeFqcn = $propertyConfig->getCustomOption(CollectionProperty::OPTION_ENTRY_TYPE);
            $isSymfonyCoreFormType = null !== u($entryTypeFqcn ?? '')->indexOf('Symfony\Component\Form\Extension\Core\Type');
            $isComplexEntry = $definesEntryType && !$isSymfonyCoreFormType;

            $propertyConfig->setCustomOption(CollectionProperty::OPTION_ENTRY_IS_COMPLEX, $isComplexEntry);
        }

        $propertyConfig->setFormattedValue($this->formatCollection($propertyConfig, $action));
    }

    private function formatCollection(PropertyConfigInterface $propertyConfig, string $action)
    {
        $doctrineMetadata = $propertyConfig->getDoctrineMetadata();
        if ('array' !== $doctrineMetadata->get('type') && !$propertyConfig->getValue() instanceof PersistentCollection) {
            return $this->countNumElements($propertyConfig->getValue());
        }

        $collectionItemsAsText = [];
        foreach ($propertyConfig->getValue() as $item) {
            if (!\is_string($item) && !method_exists($item, '__toString')) {
                return $this->countNumElements($propertyConfig->getValue());
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
