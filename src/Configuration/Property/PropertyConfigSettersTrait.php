<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration\Property;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Property\PropertyConfigInterface;

trait PropertyConfigSettersTrait
{
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    public function setFormattedValue($value): self
    {
        $this->formattedValue = $value;

        return $this;
    }

    public function setVirtual(bool $isVirtual): self
    {
        $this->virtual = $isVirtual;

        return $this;
    }

    public function setResolvedTemplatePath(string $templatePath): self
    {
        $this->resolvedTemplatePath = $templatePath;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = str_replace(' ', '-', $type);

        return $this;
    }

    public function setFormType(string $formType): self
    {
        $this->formType = $formType;

        return $this;
    }

    public function setFormTypeOptions(array $options): self
    {
        $this->formTypeOptions = $options;

        return $this;
    }

    public function setSortable(bool $isSortable): self
    {
        $this->sortable = $isSortable;

        return $this;
    }

    public function setPermission(string $role): self
    {
        $this->permission = $role;

        return $this;
    }

    public function setTextAlign(string $textAlign): self
    {
        $validOptions = ['left', 'center', 'right'];
        if (!in_array($textAlign, $validOptions)) {
            throw new \InvalidArgumentException(sprintf('The value of the "textAlign" option can only be one of these: "%s" ("%s" was given).', implode(',', $validOptions), $textAlign));
        }

        $this->textAlign = $textAlign;

        return $this;
    }

    public function setHelp(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    public function setTranslationParams(array $params): self
    {
        $this->translationParams = $params;

        return $this;
    }

    public function setTemplatePath(string $path): self
    {
        $this->templatePath = $path;
        $this->templateName = null;

        return $this;
    }

    public function setTemplateName(string $name): self
    {
        $this->templateName = $name;
        $this->templatePath = null;

        return $this;
    }

    public function addCssFiles(string ...$assetPaths): self
    {
        $this->cssFiles = array_merge($this->cssFiles, $assetPaths);

        return $this;
    }

    public function addJsFiles(string ...$assetPaths): self
    {
        $this->jsFiles = array_merge($this->jsFiles, $assetPaths);

        return $this;
    }

    public function addHtmlContentsToHead(string ...$contents): self
    {
        $this->headContents = array_merge($this->headContents, $contents);

        return $this;
    }

    public function addHtmlContentsToBody(string ...$contents): self
    {
        $this->bodyContents = array_merge($this->bodyContents, $contents);

        return $this;
    }

    public function setCustomOption(string $optionName, $optionValue): self
    {
        $this->customOptions[$optionName] = $optionValue;

        return $this;
    }

    public function setCustomOptions(array $options): self
    {
        foreach ($options as $optionName => $optionValue) {
            $this->setCustomOption($optionName, $optionValue);
        }

        return $this;
    }
}
