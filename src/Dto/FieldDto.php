<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Provider\UlidProvider;
use Symfony\Component\HttpFoundation\ParameterBag;

final class FieldDto
{
    private $fieldFqcn;
    private $name;
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

    public function __construct()
    {
        $this->cssClass = '';
        $this->formTypeOptions = [];
        $this->translationParameters = [];
        $this->assets = new AssetsDto();
        $this->customOptions = new ParameterBag();
    }

    public function getUniqueId(): string
    {
        return UlidProvider::new();
    }

    public function getFieldFqcn(): string
    {
        return $this->fieldFqcn;
    }

    public function setFieldFqcn(string $fieldFqcn): void
    {
        $this->fieldFqcn = $fieldFqcn;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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

    public function setFormatValueCallable(callable $callable): void
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
        return $this->formTypeOptions;
    }

    public function getFormTypeOption($optionName)
    {
        return $this->formTypeOptions[$optionName] ?? null;
    }

    public function setFormTypeOptions(array $formTypeOptions): void
    {
        $this->formTypeOptions = $formTypeOptions;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, $optionValue): self
    {
        // Code copied from https://github.com/adbario/php-dot-notation/blob/dc4053b44d71a5cf782e6c59dcbf09c78f036ceb/src/Dot.php#L437
        // (c) Riku Särkinen <riku@adbar.io> - MIT License
        $formTypeOptions = &$this->formTypeOptions;
        foreach (explode('.', $optionName) as $key) {
            if (!isset($formTypeOptions[$key]) || !\is_array($formTypeOptions[$key])) {
                $formTypeOptions[$key] = [];
            }

            $formTypeOptions = &$formTypeOptions[$key];
        }

        $formTypeOptions = $optionValue;

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOptionIfNotSet(string $optionName, $optionValue): self
    {
        if (!$this->arrayNestedKeyExists($this->formTypeOptions, $optionName)) {
            $this->setFormTypeOption($optionName, $optionValue);
        }

        return $this;
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
        $this->cssClass = $cssClass;
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

    public function getCustomOptions(): ParameterBag
    {
        return $this->customOptions;
    }

    public function getCustomOption(string $optionName)
    {
        return $this->customOptions->get($optionName);
    }

    public function setCustomOptions(ParameterBag $customOptions): void
    {
        $this->customOptions = $customOptions;
    }

    public function setCustomOption(string $optionName, $optionValue): void
    {
        $this->customOptions->set($optionName, $optionValue);
    }

    public function getDoctrineMetadata(): ParameterBag
    {
        return $this->doctrineMetadata;
    }

    public function setDoctrineMetadata(array $metadata): void
    {
        $this->doctrineMetadata = new ParameterBag($metadata);
    }

    private function arrayNestedKeyExists(array $array, $key): bool
    {
        // Code copied from https://github.com/adbario/php-dot-notation/blob/dc4053b44d71a5cf782e6c59dcbf09c78f036ceb/src/Dot.php#L222
        // (c) Riku Särkinen <riku@adbar.io> - MIT License
        if (\array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!\is_array($array) || !\array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }
}
