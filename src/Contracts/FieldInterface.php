<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface FieldInterface
{
    // mandatory options for all fields
    public function setDefaultOptions(OptionsResolver $resolver): void;

    // custom options defined by a particular field
    public function setCustomOptions(OptionsResolver $resolver): void;

    // optional CSS, JS assets needed by this field and added to the rendered page
    public function addAssets(array $assets): self;

    public function setType(string $type): self;

    public function setFormType(string $formType): self;

    public function setFormTypeOptions(array $options): self;

    public function setTextAlign(string $textAlign): self;

    public function setPermission(string $role): self;

    public function setHelp(string $help): self;

    public function setCssClass(string $cssClass): self;

    public function setDefaultTemplatePath(string $path): self;

    public function setCustomTemplatePath(string $path): self;

    public function setCustomTemplateParams(array $params): self;

    public function getProperty(): string;

    public function getLabel(): string;

    public function getType(): string;

    public function getFormType(): string;

    public function getFormTypeOptions(): array;

    public function getTextAlign(): ?string;

    public function getPermission(): ?string;

    public function getHelp(): ?string;

    public function getCssClass(): ?string;

    public function getDefaultTemplatePath(): string;

    public function getCustomTemplatePath(): ?string;

    public function getCustomTemplateParams(): array;
}
