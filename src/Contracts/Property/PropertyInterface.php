<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Property;

use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface PropertyInterface
{
    public function setType(string $type): self;

    public function setFormType(string $formType): self;

    public function setFormTypeOptions(array $options): self;

    public function setSortable(bool $isSortable): self;

    public function setPermission(string $role): self;

    public function setTextAlign(string $textAlign): self;

    public function setHelp(string $help): self;

    public function setCssClass(string $cssClass): self;

    public function setTranslationParams(array $params): self;

    public function setTemplateName(string $name): self;

    public function setTemplatePath(string $path): self;

    public function setCustomTemplateParams(array $params): self;

    // optional paths of the CSS, JS assets needed by this field and added to the rendered page
    public function addAssets(string ...$assetPaths): self;

    // custom options defined by a particular field
    public function setCustomOptions(OptionsResolver $resolver): void;

    // mandatory options for all fields
    public function setDefaultOptions(OptionsResolver $resolver): void;

    public function getAsDto(): PropertyDto;
}
