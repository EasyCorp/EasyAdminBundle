<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait FieldTrait
{
    private FieldDto $dto;

    final protected function __construct()
    {
        $this->dto = new FieldDto();
    }

    public function setFieldFqcn(string $fieldFqcn): FieldInterface
    {
        $this->getAsDto()->setFieldFqcn($fieldFqcn);

        return $this;
    }

    public function setProperty(string $propertyName): FieldInterface
    {
        $this->getAsDto()->setProperty($propertyName);

        return $this;
    }

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public function setLabel($label): FieldInterface
    {
        $this->getAsDto()->setLabel($label);

        return $this;
    }

    public function setValue($value): FieldInterface
    {
        $this->getAsDto()->setValue($value);

        return $this;
    }

    public function setFormattedValue($value): FieldInterface
    {
        $this->getAsDto()->setFormattedValue($value);

        return $this;
    }

    public function formatValue(?callable $callable): FieldInterface
    {
        $this->getAsDto()->setFormatValueCallable($callable);

        return $this;
    }

    public function setVirtual(bool $isVirtual): FieldInterface
    {
        $this->getAsDto()->setVirtual($isVirtual);

        return $this;
    }

    public function setDisabled(bool $disabled = true): FieldInterface
    {
        $this->getAsDto()->setFormTypeOption('disabled', $disabled);

        return $this;
    }

    public function setRequired(bool $isRequired): FieldInterface
    {
        $this->getAsDto()->setFormTypeOption('required', $isRequired);

        return $this;
    }

    public function setEmptyData($emptyData = null): FieldInterface
    {
        $this->getAsDto()->setFormTypeOption('empty_data', $emptyData);

        return $this;
    }

    public function setFormType(string $formTypeFqcn): FieldInterface
    {
        $this->getAsDto()->setFormType($formTypeFqcn);

        return $this;
    }

    public function setFormTypeOptions(array $options): FieldInterface
    {
        $this->getAsDto()->setFormTypeOptions($options);

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, $optionValue): FieldInterface
    {
        $this->getAsDto()->setFormTypeOption($optionName, $optionValue);

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOptionIfNotSet(string $optionName, $optionValue): FieldInterface
    {
        $this->getAsDto()->setFormTypeOptionIfNotSet($optionName, $optionValue);

        return $this;
    }

    public function setSortable(bool $isSortable): FieldInterface
    {
        $this->getAsDto()->setSortable($isSortable);

        return $this;
    }

    public function setPermission(string $permission): FieldInterface
    {
        $this->getAsDto()->setPermission($permission);

        return $this;
    }

    /**
     * @param string $textAlign It can be 'left', 'center' or 'right'
     */
    public function setTextAlign(string $textAlign): FieldInterface
    {
        $validOptions = [TextAlign::LEFT, TextAlign::CENTER, TextAlign::RIGHT];
        if (!\in_array($textAlign, $validOptions, true)) {
            throw new \InvalidArgumentException(sprintf('The value of the "textAlign" option can only be one of these: "%s" ("%s" was given).', implode(',', $validOptions), $textAlign));
        }

        $this->getAsDto()->setTextAlign($textAlign);

        return $this;
    }

    public function setHelp(TranslatableInterface|string $help): FieldInterface
    {
        $this->getAsDto()->setHelp($help);

        return $this;
    }

    public function addCssClass(string $cssClass): FieldInterface
    {
        $this->getAsDto()->setCssClass($this->getAsDto()->getCssClass().' '.$cssClass);

        return $this;
    }

    public function setCssClass(string $cssClass): FieldInterface
    {
        $this->getAsDto()->setCssClass($cssClass);

        return $this;
    }

    public function setTranslationParameters(array $parameters): FieldInterface
    {
        $this->getAsDto()->setTranslationParameters($parameters);

        return $this;
    }

    public function setTemplateName(string $name): FieldInterface
    {
        $this->getAsDto()->setTemplateName($name);
        $this->getAsDto()->setTemplatePath(null);

        return $this;
    }

    public function setTemplatePath(string $path): FieldInterface
    {
        $this->getAsDto()->setTemplateName(null);
        $this->getAsDto()->setTemplatePath($path);

        return $this;
    }

    public function addFormTheme(string ...$formThemePaths): FieldInterface
    {
        foreach ($formThemePaths as $formThemePath) {
            $this->getAsDto()->addFormTheme($formThemePath);
        }

        return $this;
    }

    public function addWebpackEncoreEntries(Asset|string ...$entryNamesOrAssets): FieldInterface
    {
        if (!class_exists('Symfony\\WebpackEncoreBundle\\Twig\\EntryFilesTwigExtension')) {
            throw new \RuntimeException('You are trying to add Webpack Encore entries in a field but Webpack Encore is not installed in your project. Try running "composer require symfony/webpack-encore-bundle"');
        }

        foreach ($entryNamesOrAssets as $entryNameOrAsset) {
            if (\is_string($entryNameOrAsset)) {
                $this->getAsDto()->addWebpackEncoreAsset(new AssetDto($entryNameOrAsset));
            } else {
                $this->getAsDto()->addWebpackEncoreAsset($entryNameOrAsset->getAsDto());
            }
        }

        return $this;
    }

    public function addCssFiles(Asset|string ...$pathsOrAssets): FieldInterface
    {
        foreach ($pathsOrAssets as $pathOrAsset) {
            if (\is_string($pathOrAsset)) {
                $this->getAsDto()->addCssAsset(new AssetDto($pathOrAsset));
            } else {
                $this->getAsDto()->addCssAsset($pathOrAsset->getAsDto());
            }
        }

        return $this;
    }

    public function addJsFiles(Asset|string ...$pathsOrAssets): FieldInterface
    {
        foreach ($pathsOrAssets as $pathOrAsset) {
            if (\is_string($pathOrAsset)) {
                $this->getAsDto()->addJsAsset(new AssetDto($pathOrAsset));
            } else {
                $this->getAsDto()->addJsAsset($pathOrAsset->getAsDto());
            }
        }

        return $this;
    }

    public function addHtmlContentsToHead(string ...$contents): FieldInterface
    {
        foreach ($contents as $content) {
            $this->getAsDto()->addHtmlContentToHead($content);
        }

        return $this;
    }

    public function addHtmlContentsToBody(string ...$contents): FieldInterface
    {
        foreach ($contents as $content) {
            $this->getAsDto()->addHtmlContentToBody($content);
        }

        return $this;
    }

    public function setCustomOption(string $optionName, $optionValue): FieldInterface
    {
        $this->getAsDto()->setCustomOption($optionName, $optionValue);

        return $this;
    }

    public function setCustomOptions(array $options): FieldInterface
    {
        $this->getAsDto()->setCustomOptions($options);

        return $this;
    }

    public function hideOnDetail(): FieldInterface
    {
        $displayedOn = $this->getAsDto()->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_DETAIL);

        $this->getAsDto()->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideOnForm(): FieldInterface
    {
        $displayedOn = $this->getAsDto()->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_NEW);
        $displayedOn->delete(Crud::PAGE_EDIT);

        $this->getAsDto()->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideWhenCreating(): FieldInterface
    {
        $displayedOn = $this->getAsDto()->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_NEW);

        $this->getAsDto()->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideWhenUpdating(): FieldInterface
    {
        $displayedOn = $this->getAsDto()->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_EDIT);

        $this->getAsDto()->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideOnIndex(): FieldInterface
    {
        $displayedOn = $this->getAsDto()->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_INDEX);

        $this->getAsDto()->setDisplayedOn($displayedOn);

        return $this;
    }

    public function onlyOnDetail(): FieldInterface
    {
        $this->getAsDto()->setDisplayedOn(KeyValueStore::new([Crud::PAGE_DETAIL => Crud::PAGE_DETAIL]));

        return $this;
    }

    public function onlyOnForms(): FieldInterface
    {
        $this->getAsDto()->setDisplayedOn(KeyValueStore::new([
            Crud::PAGE_NEW => Crud::PAGE_NEW,
            Crud::PAGE_EDIT => Crud::PAGE_EDIT,
        ]));

        return $this;
    }

    public function onlyOnIndex(): FieldInterface
    {
        $this->getAsDto()->setDisplayedOn(KeyValueStore::new([Crud::PAGE_INDEX => Crud::PAGE_INDEX]));

        return $this;
    }

    public function onlyWhenCreating(): FieldInterface
    {
        $this->getAsDto()->setDisplayedOn(KeyValueStore::new([Crud::PAGE_NEW => Crud::PAGE_NEW]));

        return $this;
    }

    public function onlyWhenUpdating(): FieldInterface
    {
        $this->getAsDto()->setDisplayedOn(KeyValueStore::new([Crud::PAGE_EDIT => Crud::PAGE_EDIT]));

        return $this;
    }

    /**
     * @param int|string $cols An integer with the number of columns that this field takes (e.g. 6),
     *                         or a string with responsive col CSS classes (e.g. 'col-6 col-sm-4 col-lg-3')
     */
    public function setColumns(int|string $cols): FieldInterface
    {
        $this->getAsDto()->setColumns(\is_int($cols) ? 'col-md-'.$cols : $cols);

        return $this;
    }

    /**
     * Used to define the columns of fields when users don't define the
     * columns explicitly using the setColumns() method.
     * This should only be used if you create a custom EasyAdmin field,
     * not when configuring fields in your backend.
     *
     * @internal
     */
    public function setDefaultColumns(int|string $cols): FieldInterface
    {
        $this->getAsDto()->setDefaultColumns(\is_int($cols) ? 'col-md-'.$cols : $cols);

        return $this;
    }

    public function getAsDto(): FieldDto
    {
        return $this->dto;
    }

    public function setIcon(?string $iconCssClass, string $invokingMethod = 'FormField::setIcon()'): FieldInterface
    {
        $iconCssClass = $this->fixIconFormat(
            $iconCssClass,
            $invokingMethod
        );

        $this->getAsDto()->setCustomOption(AbstractField::OPTION_ICON, $iconCssClass);

        return $this;
    }

    private function fixIconFormat(?string $icon, string $methodName = 'FormField::setIcon()'): ?string
    {
        if (null === $icon) {
            return null;
        }

        if (!str_contains($icon, 'fa-') && !str_contains($icon, 'far-') && !str_contains($icon, 'fab-')) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.4.0',
                'The value passed as the $icon argument in "%s" method must be the full FontAwesome CSS class of the icon. For example, if you passed "user" before, you now must pass "fa fa-user" (or any style variant like "fa fa-solid fa-user").',
                $methodName
            );

            $icon = sprintf('fa fa-%s', $icon);
        }

        return $icon;
    }
}
