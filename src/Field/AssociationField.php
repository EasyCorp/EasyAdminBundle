<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class AssociationField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_AUTOCOMPLETE = 'autocomplete';
    public const OPTION_AUTOCOMPLETE_CALLBACK = 'autocompleteCallback';
    public const OPTION_AUTOCOMPLETE_TEMPLATE = 'autocompleteTemplate';
    public const OPTION_EMBEDDED_CRUD_FORM_CONTROLLER = 'crudControllerFqcn';
    /** @deprecated since easycorp/easyadmin-bundle 4.4.3 use AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER */
    public const OPTION_CRUD_CONTROLLER = self::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER;
    public const OPTION_WIDGET = 'widget';
    public const OPTION_QUERY_BUILDER_CALLABLE = 'queryBuilderCallable';
    /** @internal this option is intended for internal use only */
    public const OPTION_RELATED_URL = 'relatedUrl';
    /** @internal this option is intended for internal use only */
    public const OPTION_DOCTRINE_ASSOCIATION_TYPE = 'associationType';

    public const WIDGET_AUTOCOMPLETE = 'autocomplete';
    public const WIDGET_NATIVE = 'native';

    /** @internal this option is intended for internal use only */
    public const PARAM_AUTOCOMPLETE_CONTEXT = 'autocompleteContext';

    /** @internal this option is intended for internal use only */
    public const OPTION_RENDER_AS_EMBEDDED_FORM = 'renderAsEmbeddedForm';

    public const OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME = 'crudNewPageName';
    public const OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME = 'crudEditPageName';
    // the name of the property in the associated entity used to sort the results (only for *-To-One associations)
    public const OPTION_SORT_PROPERTY = 'sortProperty';
    public const OPTION_ESCAPE_HTML_CONTENTS = 'escapeHtml';
    public const OPTION_PREFERRED_CHOICES = 'preferredChoices';

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/association')
            ->setFormType(EntityType::class)
            ->addCssClass('field-association')
            ->setDefaultColumns('col-md-7 col-xxl-6')
            ->setCustomOption(self::OPTION_AUTOCOMPLETE, false)
            ->setCustomOption(self::OPTION_AUTOCOMPLETE_CALLBACK, null)
            ->setCustomOption(self::OPTION_AUTOCOMPLETE_TEMPLATE, null)
            ->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, null)
            ->setCustomOption(self::OPTION_WIDGET, self::WIDGET_AUTOCOMPLETE)
            ->setCustomOption(self::OPTION_QUERY_BUILDER_CALLABLE, null)
            ->setCustomOption(self::OPTION_RELATED_URL, null)
            ->setCustomOption(self::OPTION_DOCTRINE_ASSOCIATION_TYPE, null)
            ->setCustomOption(self::OPTION_RENDER_AS_EMBEDDED_FORM, false)
            ->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME, null)
            ->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME, null)
            ->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, true)
            ->setCustomOption(self::OPTION_PREFERRED_CHOICES, null);
    }

    public function autocomplete(bool $enable = true, ?callable $callback = null, ?string $template = null, bool $renderAsHtml = false): self
    {
        if (!$enable) {
            return $this;
        }

        $this->setCustomOption(self::OPTION_AUTOCOMPLETE, true);

        if (null !== $callback) {
            $this->setCustomOption(self::OPTION_AUTOCOMPLETE_CALLBACK, $callback);
        }

        if (null !== $template) {
            $this->setCustomOption(self::OPTION_AUTOCOMPLETE_TEMPLATE, $template);
        }

        // the renderAsHtml parameter controls the same option as renderAsHtml() method
        $this->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, !$renderAsHtml);

        return $this;
    }

    public function renderAsNativeWidget(bool $asNative = true): self
    {
        $this->setCustomOption(self::OPTION_WIDGET, $asNative ? self::WIDGET_NATIVE : self::WIDGET_AUTOCOMPLETE);

        return $this;
    }

    public function setCrudController(string $crudControllerFqcn): self
    {
        $this->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, $crudControllerFqcn);

        return $this;
    }

    public function setQueryBuilder(\Closure $queryBuilderCallable): self
    {
        $this->setCustomOption(self::OPTION_QUERY_BUILDER_CALLABLE, $queryBuilderCallable);

        return $this;
    }

    public function renderAsEmbeddedForm(?string $crudControllerFqcn = null, ?string $crudNewPageName = null, ?string $crudEditPageName = null): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_EMBEDDED_FORM, true);
        $this->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER, $crudControllerFqcn);
        $this->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME, $crudNewPageName);
        $this->setCustomOption(self::OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME, $crudEditPageName);

        return $this;
    }

    public function setSortProperty(string $orderProperty): self
    {
        $this->setCustomOption(self::OPTION_SORT_PROPERTY, $orderProperty);

        return $this;
    }

    public function renderAsHtml(bool $asHtml = true): self
    {
        $this->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, !$asHtml);

        return $this;
    }

    /**
     * Sets the preferred entities that will be displayed at the top of the dropdown,
     * visually separated from the rest of entities.
     *
     * You can pass an array of entity objects or their primary key values:
     *   ->setPreferredChoices([1, 2, 3])
     *   ->setPreferredChoices([$featuredCategory1, $featuredCategory2])
     *
     * Or a callable that receives an entity and returns true for preferred choices:
     *   ->setPreferredChoices(fn (Category $category) => $category->isFeatured())
     *
     * Note: This option is not compatible with remote autocomplete (->autocomplete()).
     *
     * @param array<mixed>|callable $preferredChoices
     */
    public function setPreferredChoices(array|callable $preferredChoices): self
    {
        $this->setCustomOption(self::OPTION_PREFERRED_CHOICES, $preferredChoices);

        return $this;
    }
}
