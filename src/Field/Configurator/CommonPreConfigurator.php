<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use function Symfony\Component\String\u;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CommonPreConfigurator implements FieldConfiguratorInterface
{
    private $translator;
    private $propertyAccessor;

    public function __construct(TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor)
    {
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        // this configurator applies to all kinds of properties
        return true;
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $translationDomain = $context->getI18n()->getTranslationDomain();

        $value = $this->buildValueOption($field, $entityDto);
        $field->setValue($value);
        $field->setFormattedValue($value);

        $label = $this->buildLabelOption($field, $translationDomain, $context->getCrud()->getCurrentPage());
        $field->setLabel($label);

        $isRequired = $this->buildRequiredOption($field, $entityDto);
        $field->setFormTypeOption('required', $isRequired);

        $isSortable = $this->buildSortableOption($field, $entityDto);
        $field->setSortable($isSortable);

        $isVirtual = $this->buildVirtualOption($field, $entityDto);
        $field->setVirtual($isVirtual);

        $templatePath = $this->buildTemplatePathOption($context, $field, $entityDto);
        $field->setTemplatePath($templatePath);

        $doctrineMetadata = $entityDto->hasProperty($field->getProperty()) ? $entityDto->getPropertyMetadata($field->getProperty())->all() : [];
        $field->setDoctrineMetadata($doctrineMetadata);

        if (null !== $field->getHelp()) {
            $helpMessage = $this->buildHelpOption($field, $translationDomain);
            $field->setHelp($helpMessage);

            $field->setFormTypeOptionIfNotSet('help', $helpMessage);
            $field->setFormTypeOptionIfNotSet('help_html', true);
            $field->setFormTypeOptionIfNotSet('help_translation_parameters', $field->getTranslationParameters());
        }

        if (!empty($field->getCssClass())) {
            $field->setFormTypeOptionIfNotSet('row_attr.class', $field->getCssClass());
        }

        if (null !== $field->getTextAlign()) {
            $field->setFormTypeOptionIfNotSet('attr.align', $field->getTextAlign());
        }

        $field->setFormTypeOptionIfNotSet('label', $field->getLabel());
        $field->setFormTypeOptionIfNotSet('label_translation_parameters', $field->getTranslationParameters());
    }

    private function buildValueOption(FieldDto $field, EntityDto $entityDto)
    {
        $entityInstance = $entityDto->getInstance();
        $propertyName = $field->getProperty();

        if (!$this->propertyAccessor->isReadable($entityInstance, $propertyName)) {
            return null;
        }

        return $this->propertyAccessor->getValue($entityInstance, $propertyName);
    }

    private function buildHelpOption(FieldDto $field, string $translationDomain): ?string
    {
        if ((null === $help = $field->getHelp()) || empty($help)) {
            return $help;
        }

        return $this->translator->trans($help, $field->getTranslationParameters(), $translationDomain);
    }

    private function buildLabelOption(FieldDto $field, string $translationDomain, ?string $currentPage): ?string
    {
        // don't autogenerate a label for these special fields (there's a dedicated configurator for them)
        if (FormField::class === $field->getFieldFqcn()) {
            $label = $field->getLabel();

            return empty($label) ? $label : $this->translator->trans($label, $field->getTranslationParameters(), $translationDomain);
        }

        // if an Avatar field doesn't define its label, don't autogenerate it for the 'index' page
        // (because the table of the 'index' page looks better without a header in the avatar column)
        if (Action::INDEX === $currentPage && null === $field->getLabel() && AvatarField::class === $field->getFieldFqcn()) {
            $field->setLabel(false);
        }

        // it field doesn't define its label explicitly, generate an automatic
        // label based on the field's field name
        if (null === $label = $field->getLabel()) {
            $label = $this->humanizeString($field->getProperty());
        }

        if (empty($label)) {
            return $label;
        }

        // don't translate labels in form-related pages because Symfony Forms translates
        // labels automatically and that causes false "translation is missing" errors
        if (\in_array($currentPage, [Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            return $label;
        }

        return $this->translator->trans($label, $field->getTranslationParameters(), $translationDomain);
    }

    private function buildSortableOption(FieldDto $field, EntityDto $entityDto): bool
    {
        if (null !== $isSortable = $field->isSortable()) {
            return $isSortable;
        }

        return $entityDto->hasProperty($field->getProperty());
    }

    private function buildVirtualOption(FieldDto $field, EntityDto $entityDto): bool
    {
        return !$entityDto->hasProperty($field->getProperty());
    }

    private function buildTemplatePathOption(AdminContext $adminContext, FieldDto $field, EntityDto $entityDto): string
    {
        if (null !== $templatePath = $field->getTemplatePath()) {
            return $templatePath;
        }

        $isPropertyReadable = $this->propertyAccessor->isReadable($entityDto->getInstance(), $field->getProperty());
        if (!$isPropertyReadable) {
            return $adminContext->getTemplatePath('label/inaccessible');
        }

        if (null === $templateName = $field->getTemplateName()) {
            throw new \RuntimeException(sprintf('Fields must define either their templateName or their templatePath. None given for "%s" field.', $field->getProperty()));
        }

        return $adminContext->getTemplatePath($templateName);
    }

    private function buildRequiredOption(FieldDto $field, EntityDto $entityDto): bool
    {
        if (null !== $isRequired = $field->getFormTypeOption('required')) {
            return $isRequired;
        }

        // consider that virtual properties are not required
        if (!$entityDto->hasProperty($field->getProperty())) {
            return false;
        }

        // TODO: fix this and see if there's any way to check if an association is nullable
        if ($entityDto->isAssociation($field->getProperty())) {
            return false;
        }

        $doctrinePropertyMetadata = $entityDto->getPropertyMetadata($field->getProperty());

        // TODO: check if it's correct to never make a boolean value required
        // I guess it's correct because Symfony Forms treat NULL as FALSE by default (i.e. in the database the value won't be NULL)
        if ('boolean' === $doctrinePropertyMetadata->get('type')) {
            return false;
        }

        return !$doctrinePropertyMetadata->get('nullable');
    }

    private function humanizeString(string $string): string
    {
        $uString = u($string);
        $upperString = $uString->upper()->toString();

        // this prevents humanizing all-uppercase labels (e.g. 'UUID' -> 'U u i d')
        // and other special labels which look better in uppercase
        if ($uString->toString() === $upperString || \in_array($upperString, ['ID', 'URL'], true)) {
            return $upperString;
        }

        return $uString
            ->replaceMatches('/([A-Z])/', '_$1')
            ->replaceMatches('/[_\s]+/', ' ')
            ->trim()
            ->lower()
            ->title(true);
    }
}
