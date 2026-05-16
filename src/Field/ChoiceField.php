<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ChoiceField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_ALLOW_MULTIPLE_CHOICES = 'allowMultipleChoices';
    public const OPTION_AUTOCOMPLETE = 'autocomplete';
    public const OPTION_CHOICES = 'choices';
    public const OPTION_USE_TRANSLATABLE_CHOICES = 'useTranslatableChoices';
    public const OPTION_RENDER_AS_BADGES = 'renderAsBadges';
    public const OPTION_RENDER_EXPANDED = 'renderExpanded';
    public const OPTION_WIDGET = 'widget';
    public const OPTION_ESCAPE_HTML_CONTENTS = 'escapeHtml';
    public const OPTION_PREFERRED_CHOICES = 'preferredChoices';

    public const VALID_BADGE_TYPES = ['success', 'warning', 'danger', 'info', 'primary', 'secondary', 'light', 'dark'];

    public const WIDGET_AUTOCOMPLETE = 'autocomplete';
    public const WIDGET_NATIVE = 'native';

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/choice')
            ->setFormType(ChoiceType::class)
            ->addCssClass('field-select')
            ->setDefaultColumns('') // this is set dynamically in the field configurator
            ->setCustomOption(self::OPTION_CHOICES, null)
            ->setCustomOption(self::OPTION_USE_TRANSLATABLE_CHOICES, false)
            ->setCustomOption(self::OPTION_ALLOW_MULTIPLE_CHOICES, false)
            ->setCustomOption(self::OPTION_RENDER_AS_BADGES, null)
            ->setCustomOption(self::OPTION_RENDER_EXPANDED, false)
            ->setCustomOption(self::OPTION_WIDGET, null)
            ->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, true)
            ->setCustomOption(self::OPTION_PREFERRED_CHOICES, null);
    }

    public function allowMultipleChoices(bool $allow = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_MULTIPLE_CHOICES, $allow);

        return $this;
    }

    public function autocomplete(): self
    {
        $this->setCustomOption(self::OPTION_AUTOCOMPLETE, true);

        return $this;
    }

    /**
     * Given choices must follow the same format used in Symfony Forms:
     * ['Label visible to users' => 'submitted_value', ...].
     *
     * If the contents of the choice values are translatable objects, use the
     * setTranslatableChoices() method instead:
     * ['submitted_value' => t('Label visible to users'), ...].
     *
     * In addition to an array, you can use a PHP callback, which is passed the instance
     * of the current entity (it can be null) and the FieldDto as the second argument:
     * ->setChoices(fn () => ['foo' => 1, 'bar' => 2])
     * ->setChoices(fn (?MyEntity $foo) => $foo->someField()->getChoices())
     * ->setChoices(fn (?MyEntity $foo, FieldDto $field) => ...)
     *
     * @param array<string|int, mixed>|callable $choiceGenerator
     */
    public function setChoices(array|callable $choiceGenerator): self
    {
        $this->setCustomOption(self::OPTION_CHOICES, $choiceGenerator);

        return $this;
    }

    /**
     * When the contents of the choices use translatable objects, you can't use the
     * setChoices() method because PHP doesn't allow using objects as array keys.
     *
     * Given choices must follow the opposite of the format used in Symfony Forms:
     * ['submitted_value' => t('Label visible to users'), ...].
     *
     * @param array<string|int, mixed>|callable $choiceGenerator
     */
    public function setTranslatableChoices(array|callable $choiceGenerator): self
    {
        $this->setChoices($choiceGenerator);
        $this->setCustomOption(self::OPTION_USE_TRANSLATABLE_CHOICES, true);

        return $this;
    }

    /**
     * Possible values of $badgeSelector:
     *   * true: all values are displayed as 'secondary' badges
     *   * false: no badges are displayed; values are displayed as regular text
     *   * array: [$fieldValue => $badgeType, ...] (e.g. ['foo' => 'primary', 7 => 'warning', 'cancelled' => 'danger'])
     *   * callable: function(FieldDto $field): string { return '...' }
     *     (e.g. function(FieldDto $field) { return $field->getValue() < 10 ? 'warning' : 'primary'; }).
     *
     * Possible badge types: 'success', 'warning', 'danger', 'info', 'primary', 'secondary', 'light', 'dark'
     *
     * @param array<string|int, mixed>|bool|callable $badgeSelector
     */
    public function renderAsBadges(array|bool|callable $badgeSelector = true): self
    {
        if (\is_array($badgeSelector)) {
            foreach ($badgeSelector as $badgeType) {
                if (!\in_array($badgeType, self::VALID_BADGE_TYPES, true)) {
                    throw new \InvalidArgumentException(sprintf('The values of the array passed to the "%s" method must be one of the following valid badge types: "%s" ("%s" given).', __METHOD__, implode(', ', self::VALID_BADGE_TYPES), $badgeType));
                }
            }
        }

        $this->setCustomOption(self::OPTION_RENDER_AS_BADGES, $badgeSelector);

        return $this;
    }

    public function renderAsNativeWidget(bool $asNative = true): self
    {
        $this->setCustomOption(self::OPTION_WIDGET, $asNative ? self::WIDGET_NATIVE : self::WIDGET_AUTOCOMPLETE);

        return $this;
    }

    public function renderExpanded(bool $expanded = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_EXPANDED, $expanded);

        return $this;
    }

    public function escapeHtml(bool $escape = true): self
    {
        $this->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, $escape);

        return $this;
    }

    /**
     * Sets the preferred choices that will be displayed at the top of the dropdown,
     * visually separated from the rest of choices.
     *
     * You can pass an array of choice values:
     *   ->setPreferredChoices(['draft', 'published'])
     *
     * Or a callable that receives the choice value and returns true for preferred choices:
     *   ->setPreferredChoices(fn ($value) => in_array($value, ['draft', 'published']))
     *
     * @param array<mixed>|callable $preferredChoices
     */
    public function setPreferredChoices(array|callable $preferredChoices): self
    {
        $this->setCustomOption(self::OPTION_PREFERRED_CHOICES, $preferredChoices);

        return $this;
    }
}
