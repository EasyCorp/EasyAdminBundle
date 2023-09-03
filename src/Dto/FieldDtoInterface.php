<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FieldDtoInterface
{
    public function getUniqueId(): string;

    public function setUniqueId(string $uniqueId): void;

    public function isFormDecorationField(): bool;

    public function getFieldFqcn(): ?string;

    /**
     * @internal Don't use this method yourself. EasyAdmin uses it internally
     *           to set the field FQCN. It's OK to use getFieldFqcn() to get this value.
     */
    public function setFieldFqcn(string $fieldFqcn): void;

    public function getProperty(): string;

    public function setProperty(string $propertyName): void;

    /**
     * Returns the original unmodified value stored in the entity field.
     */
    public function getValue(): mixed;

    public function setValue(mixed $value): void;

    /**
     * Returns the value to be displayed for the field (it could be the
     * same as the value stored in the field or not).
     */
    public function getFormattedValue(): mixed;

    public function setFormattedValue(mixed $formattedValue): void;

    public function getFormatValueCallable(): ?callable;

    public function setFormatValueCallable(?callable $callable): void;

    /**
     * @return TranslatableInterface|string|false|null
     */
    public function getLabel();

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public function setLabel($label): void;

    public function getFormType(): ?string;

    public function setFormType(string $formTypeFqcn): void;

    public function getFormTypeOptions(): array;

    public function getFormTypeOption(string $optionName);

    public function setFormTypeOptions(array $formTypeOptions): void;

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, mixed $optionValue): void;

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOptionIfNotSet(string $optionName, mixed $optionValue): void;

    public function isSortable(): ?bool;

    public function setSortable(bool $isSortable): void;

    public function isVirtual(): ?bool;

    public function setVirtual(bool $isVirtual): void;

    public function getTextAlign(): ?string;

    public function setTextAlign(string $textAlign): void;

    public function getPermission(): ?string;

    public function setPermission(string $permission): void;

    public function getHelp(): TranslatableInterface|string|null;

    public function setHelp(TranslatableInterface|string $help): void;

    public function getCssClass(): string;

    public function setCssClass(string $cssClass): void;

    public function getColumns(): ?string;

    public function setColumns(?string $columnCssClasses): void;

    public function getDefaultColumns(): string;

    public function setDefaultColumns(string $columnCssClasses): void;

    public function getTranslationParameters(): array;

    public function setTranslationParameters(array $translationParameters): void;

    public function getTemplateName(): ?string;

    public function setTemplateName(?string $templateName): void;

    public function getTemplatePath(): ?string;

    public function setTemplatePath(?string $templatePath): void;

    public function addFormTheme(string $formThemePath): void;

    public function getFormThemes(): array;

    public function setFormThemes(array $formThemePaths): void;

    public function getAssets(): AssetsDtoInterface;

    public function setAssets(AssetsDtoInterface $assets): void;

    public function addWebpackEncoreAsset(AssetDtoInterface $assetDto): void;

    public function addCssAsset(AssetDtoInterface $assetDto): void;

    public function addJsAsset(AssetDtoInterface $assetDto): void;

    public function addHtmlContentToHead(string $htmlContent): void;

    public function addHtmlContentToBody(string $htmlContent): void;

    public function getCustomOptions(): KeyValueStore;

    public function getCustomOption(string $optionName): mixed;

    public function setCustomOptions(array $customOptions): void;

    public function setCustomOption(string $optionName, mixed $optionValue): void;

    public function getDoctrineMetadata(): KeyValueStore;

    public function setDoctrineMetadata(array $metadata): void;

    public function getDisplayedOn(): KeyValueStore;

    public function setDisplayedOn(KeyValueStore $displayedOn): void;

    public function isDisplayedOn(string $pageName): bool;
}
