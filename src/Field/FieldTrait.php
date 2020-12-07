<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait FieldTrait
{
    /** @var FieldDto */
    private $dto;

    private function __construct()
    {
        $this->dto = new FieldDto();
    }

    public function setFieldFqcn(string $fieldFqcn): self
    {
        $this->dto->setFieldFqcn($fieldFqcn);

        return $this;
    }

    public function setProperty(string $propertyName): self
    {
        $this->dto->setProperty($propertyName);

        return $this;
    }

    public function setLabel(?string $label): self
    {
        $this->dto->setLabel($label);

        return $this;
    }

    public function setValue($value): self
    {
        $this->dto->setValue($value);

        return $this;
    }

    public function setFormattedValue($value): self
    {
        $this->dto->setFormattedValue($value);

        return $this;
    }

    public function formatValue(?callable $callable): self
    {
        $this->dto->setFormatValueCallable($callable);

        return $this;
    }

    public function setVirtual(bool $isVirtual): self
    {
        $this->dto->setVirtual($isVirtual);

        return $this;
    }

    public function setRequired(bool $isRequired): self
    {
        $this->dto->setFormTypeOption('required', $isRequired);

        return $this;
    }

    public function setFormType(string $formTypeFqcn): self
    {
        $this->dto->setFormType($formTypeFqcn);

        return $this;
    }

    public function setFormTypeOptions(array $options): self
    {
        $this->dto->setFormTypeOptions($options);

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, $optionValue): self
    {
        $this->dto->setFormTypeOption($optionName, $optionValue);

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOptionIfNotSet(string $optionName, $optionValue): self
    {
        $this->dto->setFormTypeOptionIfNotSet($optionName, $optionValue);

        return $this;
    }

    public function setSortable(bool $isSortable): self
    {
        $this->dto->setSortable($isSortable);

        return $this;
    }

    public function setPermission(string $permission): self
    {
        $this->dto->setPermission($permission);

        return $this;
    }

    /**
     * @param string $textAlign It can be 'left', 'center' or 'right'
     */
    public function setTextAlign(string $textAlign): self
    {
        $validOptions = [TextAlign::LEFT, TextAlign::CENTER, TextAlign::RIGHT];
        if (!\in_array($textAlign, $validOptions, true)) {
            throw new \InvalidArgumentException(sprintf('The value of the "textAlign" option can only be one of these: "%s" ("%s" was given).', implode(',', $validOptions), $textAlign));
        }

        $this->dto->setTextAlign($textAlign);

        return $this;
    }

    public function setHelp(string $help): self
    {
        $this->dto->setHelp($help);

        return $this;
    }

    public function addCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($this->dto->getCssClass().' '.$cssClass);

        return $this;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($cssClass);

        return $this;
    }

    public function setTranslationParameters(array $parameters): self
    {
        $this->dto->setTranslationParameters($parameters);

        return $this;
    }

    public function setTemplateName(string $name): self
    {
        $this->dto->setTemplateName($name);
        $this->dto->setTemplatePath(null);

        return $this;
    }

    public function setTemplatePath(string $path): self
    {
        $this->dto->setTemplateName(null);
        $this->dto->setTemplatePath($path);

        return $this;
    }

    public function addCssFiles(string ...$assetPaths): self
    {
        foreach ($assetPaths as $path) {
            $this->dto->addCssFile($path);
        }

        return $this;
    }

    public function addJsFiles(string ...$assetPaths): self
    {
        foreach ($assetPaths as $path) {
            $this->dto->addJsFile($path);
        }

        return $this;
    }

    public function addHtmlContentsToHead(string ...$contents): self
    {
        foreach ($contents as $content) {
            $this->dto->addHtmlContentToHead($content);
        }

        return $this;
    }

    public function addHtmlContentsToBody(string ...$contents): self
    {
        foreach ($contents as $content) {
            $this->dto->addHtmlContentToBody($content);
        }

        return $this;
    }

    public function setCustomOption(string $optionName, $optionValue): self
    {
        $this->dto->setCustomOption($optionName, $optionValue);

        return $this;
    }

    public function setCustomOptions(array $options): self
    {
        $this->dto->setCustomOptions($options);

        return $this;
    }

    public function hideOnDetail(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_DETAIL);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideOnForm(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_NEW);
        $displayedOn->delete(Crud::PAGE_EDIT);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideOnIndex(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_INDEX);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function onlyOnDetail(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_DETAIL => Crud::PAGE_DETAIL]));

        return $this;
    }

    public function onlyOnForms(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([
            Crud::PAGE_NEW => Crud::PAGE_NEW,
            Crud::PAGE_EDIT => Crud::PAGE_EDIT,
        ]));

        return $this;
    }

    public function onlyOnIndex(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_INDEX => Crud::PAGE_INDEX]));

        return $this;
    }

    public function onlyWhenCreating(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_NEW => Crud::PAGE_NEW]));

        return $this;
    }

    public function onlyWhenUpdating(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_EDIT => Crud::PAGE_EDIT]));

        return $this;
    }

    public function getAsDto(): FieldDto
    {
        return $this->dto;
    }
}
