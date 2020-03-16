<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use Symfony\Component\HttpFoundation\ParameterBag;

trait FieldTrait
{
    private $type;
    private $property;
    private $value;
    private $formattedValue;
    private $formatValueCallable;
    private $label;
    private $required;
    private $formType;
    private $formTypeOptions = [];
    private $sortable;
    private $virtual;
    private $permission;
    private $textAlign;
    private $help;
    private $cssClass;
    private $translationParameters = [];
    private $templateName;
    private $templatePath;
    private $cssFiles = [];
    private $jsFiles = [];
    private $headContents = [];
    private $bodyContents = [];
    private $customOptions;
    private $doctrineMetadata;

    private function __construct()
    {
    }

    public static function new(string $propertyName, ?string $label = null): self
    {
        $field = new static();
        $field->property = $propertyName;
        $field->label = $label;

        return $field;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the original unmodified value stored in the entity field.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the value to be displayed for the field (it could be the
     * same as the value stored in the field or not).
     */
    public function getFormattedValue()
    {
        return $this->formattedValue;
    }

    public function getFormatValueCallable(): ?callable
    {
        return $this->formatValueCallable;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function isRequired(): ?bool
    {
        return $this->formTypeOptions['required'] ?? null;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions;
    }

    public function isSortable(): ?bool
    {
        return $this->sortable;
    }

    public function isVirtual(): bool
    {
        return true === $this->virtual;
    }

    public function getTextAlign(): ?string
    {
        return $this->textAlign;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }

    public function getTranslationParameters(): array
    {
        return $this->translationParameters ?? [];
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function getCssFiles(): array
    {
        return $this->cssFiles ?? [];
    }

    public function getJsFiles(): array
    {
        return $this->jsFiles ?? [];
    }

    public function getHeadContents(): array
    {
        return $this->headContents ?? [];
    }

    public function getBodyContents(): array
    {
        return $this->bodyContents ?? [];
    }

    public function getCustomOptions(): ParameterBag
    {
        return new ParameterBag($this->customOptions ?? []);
    }

    public function getCustomOption(string $optionName)
    {
        return $this->getCustomOptions()->get($optionName);
    }

    public function getDoctrineMetadata(): ParameterBag
    {
        return new ParameterBag($this->doctrineMetadata ?? []);
    }

    public function getAsDto(): FieldDto
    {
        return new FieldDto($this->getType(), $this->getProperty(), $this->getValue(), $this->getFormattedValue(), $this->getFormType(), $this->getFormTypeOptions(), $this->isSortable(), $this->isVirtual(), $this->getLabel(), $this->getPermission(), $this->getTextAlign(), $this->getHelp(), $this->getCssClass(), $this->getTranslationParameters(), $this->getTemplateName(), $this->getTemplatePath(), new AssetsDto($this->getCssFiles(), $this->getJsFiles(), $this->getHeadContents(), $this->getBodyContents()), $this->getCustomOptions(), $this->getDoctrineMetadata());
    }

    public function setType(string $type): FieldInterface
    {
        $this->type = str_replace(' ', '-', $type);

        return $this;
    }

    public function setProperty(string $propertyName): FieldInterface
    {
        $this->property = $propertyName;

        return $this;
    }

    public function setLabel(?string $label): FieldInterface
    {
        $this->label = $label;

        return $this;
    }

    public function setValue($value): FieldInterface
    {
        $this->value = $value;

        return $this;
    }

    public function setFormattedValue($value): FieldInterface
    {
        $this->formattedValue = $value;

        return $this;
    }

    public function formatValue(callable $callable): FieldInterface
    {
        $this->formatValueCallable = $callable;

        return $this;
    }

    public function setVirtual(bool $isVirtual): FieldInterface
    {
        $this->virtual = $isVirtual;

        return $this;
    }

    public function setRequired(bool $isRequired): FieldInterface
    {
        $this->formTypeOptions['required'] = $isRequired;

        return $this;
    }

    public function setFormType(string $formType): FieldInterface
    {
        $this->formType = $formType;

        return $this;
    }

    public function setFormTypeOptions(array $options): FieldInterface
    {
        $this->formTypeOptions = $options;

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, $optionValue): FieldInterface
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
    public function setFormTypeOptionIfNotSet(string $optionName, $optionValue): FieldInterface
    {
        if (!$this->arrayNestedKeyExists($this->formTypeOptions, $optionName)) {
            $this->setFormTypeOption($optionName, $optionValue);
        }

        return $this;
    }

    public function setSortable(bool $isSortable): FieldInterface
    {
        $this->sortable = $isSortable;

        return $this;
    }

    public function setPermission(string $role): FieldInterface
    {
        $this->permission = $role;

        return $this;
    }

    /**
     * @param string $textAlign It can be 'left', 'center' or 'right'
     */
    public function setTextAlign(string $textAlign): FieldInterface
    {
        $validOptions = ['left', 'center', 'right'];
        if (!\in_array($textAlign, $validOptions, true)) {
            throw new \InvalidArgumentException(sprintf('The value of the "textAlign" option can only be one of these: "%s" ("%s" was given).', implode(',', $validOptions), $textAlign));
        }

        $this->textAlign = $textAlign;

        return $this;
    }

    public function setHelp(string $help): FieldInterface
    {
        $this->help = $help;

        return $this;
    }

    public function setCssClass(string $cssClass): FieldInterface
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function setTranslationParameters(array $parameters): FieldInterface
    {
        $this->translationParameters = $parameters;

        return $this;
    }

    public function setTemplateName(string $name): FieldInterface
    {
        $this->templateName = $name;
        $this->templatePath = null;

        return $this;
    }

    public function setTemplatePath(string $path): FieldInterface
    {
        $this->templatePath = $path;
        $this->templateName = null;

        return $this;
    }

    public function addCssFiles(string ...$assetPaths): FieldInterface
    {
        $this->cssFiles = array_merge($this->cssFiles, $assetPaths);

        return $this;
    }

    public function addJsFiles(string ...$assetPaths): FieldInterface
    {
        $this->jsFiles = array_merge($this->jsFiles, $assetPaths);

        return $this;
    }

    public function addHtmlContentsToHead(string ...$contents): FieldInterface
    {
        $this->headContents = array_merge($this->headContents, $contents);

        return $this;
    }

    public function addHtmlContentsToBody(string ...$contents): FieldInterface
    {
        $this->bodyContents = array_merge($this->bodyContents, $contents);

        return $this;
    }

    public function setCustomOption(string $optionName, $optionValue): FieldInterface
    {
        $this->customOptions[$optionName] = $optionValue;

        return $this;
    }

    public function setCustomOptions(array $options): FieldInterface
    {
        $this->customOptions = $options;

        return $this;
    }

    public function setDoctrineMetadata(array $metadata): FieldInterface
    {
        $this->doctrineMetadata = $metadata;

        return $this;
    }

    private function arrayNestedKeyExists(array $array, $key): bool
    {
        if (\array_key_exists($key, $array)) {
            return true;
        }

        foreach ($array as $element) {
            if (\is_array($element) && $this->arrayNestedKeyExists($element, $key)) {
                return true;
            }
        }

        return false;
    }
}
