<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Property;

use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\HttpFoundation\ParameterBag;

interface PropertyConfigInterface
{
    public function setType(string $type);

    public function setName(string $name): self;

    public function setLabel(?string $label): self;

    public function setValue($value): self;

    public function setFormattedValue($value): self;

    public function formatValue(callable $callable): self;

    public function setVirtual(bool $isVirtual): self;

    public function setRequired(bool $isRequired): self;

    public function setFormType(string $formType): self;

    public function setFormTypeOptions(array $options): self;

    public function setSortable(bool $isSortable): self;

    public function setPermission(string $role): self;

    /**
     * @param string $textAlign It must be 'left', 'center' or 'right'
     */
    public function setTextAlign(string $textAlign): self;

    public function setHelp(string $help): self;

    public function setCssClass(string $cssClass): self;

    public function setTranslationParameters(array $parameters): self;

    public function setTemplateName(string $name): self;

    public function setTemplatePath(string $path): self;

    public function setDoctrineMetadata(array $metadata): self;

    public function addCssFiles(string ...$cssFilePaths): self;

    public function addJsFiles(string ...$jsFilePaths): self;

    public function addHtmlContentsToHead(string ...$contents): self;

    public function addHtmlContentsToBody(string ...$contents): self;

    public function setCustomOption(string $optionName, $optionValue): self;

    public function setCustomOptions(array $options): self;

    public function getName(): string;

    public function getType(): string;

    public function getValue();

    public function getFormattedValue();

    public function getFormatValueCallable(): ?callable;

    public function getLabel(): ?string;

    public function isRequired(): ?bool;

    public function getFormType(): ?string;

    public function getFormTypeOptions(): array;

    public function isSortable(): ?bool;

    public function isVirtual(): bool;

    public function getTextAlign(): ?string;

    public function getPermission(): ?string;

    public function getHelp(): ?string;

    public function getCssClass(): ?string;

    public function getTranslationParameters(): array;

    public function getTemplateName(): ?string;

    public function getTemplatePath(): ?string;

    public function getCssFiles(): array;

    public function getJsFiles(): array;

    public function getHeadContents(): array;

    public function getBodyContents(): array;

    public function getCustomOption(string $optionName);

    public function getCustomOptions(): ParameterBag;

    public function getDoctrineMetadata(): ParameterBag;

    public function getAsDto(): PropertyDto;
}
