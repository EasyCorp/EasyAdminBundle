<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use function Symfony\Component\String\u;
use Symfony\Component\Uid\Ulid;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FieldDto
{
    private $fieldFqcn;
    private $propertyName;
    private $value;
    private $formattedValue;
    private $formatValueCallable;
    private $label;
    private $formType;
    private $formTypeOptions;
    private $sortable;
    private $virtual;
    private $permission;
    private $textAlign;
    private $help;
    private $cssClass;
    private $translationParameters;
    private $templateName;
    private $templatePath;
    /** @var AssetsDto */
    private $assets;
    private $customOptions;
    private $doctrineMetadata;
    /** @internal */
    private $uniqueId;
    private $displayedOn;

    public function __construct()
    {
        $this->cssClass = '';
        $this->templateName = 'crud/field/text';
        $this->assets = new AssetsDto();
        $this->translationParameters = [];
        $this->formTypeOptions = KeyValueStore::new();
        $this->customOptions = KeyValueStore::new();
        $this->doctrineMetadata = KeyValueStore::new();
        $this->displayedOn = KeyValueStore::new([
            Crud::PAGE_INDEX => Crud::PAGE_INDEX,
            Crud::PAGE_DETAIL => Crud::PAGE_DETAIL,
            Crud::PAGE_EDIT => Crud::PAGE_EDIT,
            Crud::PAGE_NEW => Crud::PAGE_NEW,
        ]);
    }

    public function __clone()
    {
        $this->assets = clone $this->assets;
        $this->formTypeOptions = clone $this->formTypeOptions;
        $this->customOptions = clone $this->customOptions;
        $this->doctrineMetadata = clone $this->doctrineMetadata;
        $this->displayedOn = clone $this->displayedOn;
    }

    public function getUniqueId(): string
    {
        if (null !== $this->uniqueId) {
            return $this->uniqueId;
        }

        return $this->uniqueId = new Ulid();
    }

    public function isFormDecorationField(): bool
    {
        return null !== u($this->getCssClass())->indexOf('field-form_panel');
    }

    public function getFieldFqcn(): string
    {
        return $this->fieldFqcn;
    }

    /**
     * @internal Don't use this method yourself. EasyAdmin uses it internally
     *           to set the field FQCN. It's OK to use getFieldFqcn() to get this value.
     */
    public function setFieldFqcn(string $fieldFqcn): void
    {
        $this->fieldFqcn = $fieldFqcn;
    }

    public function getProperty(): string
    {
        return $this->propertyName;
    }

    public function setProperty(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * Returns the original unmodified value stored in the entity field.
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Returns the value to be displayed for the field (it could be the
     * same as the value stored in the field or not).
     */
    public function getFormattedValue()
    {
        return $this->formattedValue;
    }

    public function setFormattedValue($formattedValue): void
    {
        $this->formattedValue = $formattedValue;
    }

    public function getFormatValueCallable(): ?callable
    {
        return $this->formatValueCallable;
    }

    public function setFormatValueCallable(?callable $callable): void
    {
        $this->formatValueCallable = $callable;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function setFormType(string $formTypeFqcn): void
    {
        $this->formType = $formTypeFqcn;
    }

    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions->all();
    }

    public function getFormTypeOption(string $optionName)
    {
        return $this->formTypeOptions->get($optionName);
    }

    public function setFormTypeOptions(array $formTypeOptions): void
    {
        $this->formTypeOptions = KeyValueStore::new($formTypeOptions);
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, $optionValue): void
    {
        $this->formTypeOptions->set($optionName, $optionValue);
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOptionIfNotSet(string $optionName, $optionValue): void
    {
        $this->formTypeOptions->setIfNotSet($optionName, $optionValue);
    }

    public function isSortable(): ?bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $isSortable): void
    {
        $this->sortable = $isSortable;
    }

    public function isVirtual(): ?bool
    {
        return $this->virtual;
    }

    public function setVirtual(bool $isVirtual): void
    {
        $this->virtual = $isVirtual;
    }

    public function getTextAlign(): ?string
    {
        return $this->textAlign;
    }

    public function setTextAlign(string $textAlign): void
    {
        $this->textAlign = $textAlign;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): void
    {
        $this->permission = $permission;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setHelp(string $help): void
    {
        $this->help = $help;
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = trim($cssClass);
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    public function setTranslationParameters(array $translationParameters): void
    {
        $this->translationParameters = $translationParameters;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function setTemplateName(?string $templateName): void
    {
        $this->templateName = $templateName;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function setTemplatePath(?string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    public function getAssets(): AssetsDto
    {
        return $this->assets;
    }

    public function setAssets(AssetsDto $assets): void
    {
        $this->assets = $assets;
    }

    public function addCssFile(string $cssFilePath): void
    {
        $this->assets->addCssFile($cssFilePath);
    }

    public function addJsFile(string $jsFilePath): void
    {
        $this->assets->addJsFile($jsFilePath);
    }

    public function addHtmlContentToHead(string $htmlContent): void
    {
        $this->assets->addHtmlContentToHead($htmlContent);
    }

    public function addHtmlContentToBody(string $htmlContent): void
    {
        $this->assets->addHtmlContentToBody($htmlContent);
    }

    public function getCustomOptions(): KeyValueStore
    {
        return $this->customOptions;
    }

    public function getCustomOption(string $optionName)
    {
        return $this->customOptions->get($optionName);
    }

    public function setCustomOptions(array $customOptions): void
    {
        $this->customOptions = KeyValueStore::new($customOptions);
    }

    public function setCustomOption(string $optionName, $optionValue): void
    {
        $this->customOptions->set($optionName, $optionValue);
    }

    public function getDoctrineMetadata(): KeyValueStore
    {
        return $this->doctrineMetadata;
    }

    public function setDoctrineMetadata(array $metadata): void
    {
        $this->doctrineMetadata = KeyValueStore::new($metadata);
    }

    public function getDisplayedOn(): KeyValueStore
    {
        return $this->displayedOn;
    }

    public function setDisplayedOn(KeyValueStore $displayedOn): void
    {
        $this->displayedOn = $displayedOn;
    }

    public function isDisplayedOn(string $pageName): bool
    {
        return $this->displayedOn->has($pageName);
    }
}
