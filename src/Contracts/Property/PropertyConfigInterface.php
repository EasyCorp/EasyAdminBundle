<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Property;

use EasyCorp\Bundle\EasyAdminBundle\Dto\PropertyDto;

interface PropertyConfigInterface
{
    public function setType(string $type);

    public function setFormType(string $formType);

    public function setFormTypeOptions(array $options);

    public function setSortable(bool $isSortable);

    public function setPermission(string $role);

    /**
     * @param string $textAlign It must be 'left', 'center' or 'right'
     */
    public function setTextAlign(string $textAlign);

    public function setHelp(string $help);

    public function setCssClass(string $cssClass);

    public function setTranslationParams(array $params);

    public function setTemplateName(string $name);

    public function setTemplatePath(string $path);

    public function addCssFiles(string ...$cssFilePaths);

    public function addJsFiles(string ...$jsFilePaths);

    public function addHtmlContentsToHead(string ...$contents);

    public function addHtmlContentsToBody(string ...$contents);

    public function setCustomOption(string $optionName, $optionValue);

    public function setCustomOptions(array $options);

    public function getAsDto(): PropertyDto;
}
