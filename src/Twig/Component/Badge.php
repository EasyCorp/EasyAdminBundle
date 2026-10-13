<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Provider\ComponentDefaultsProvider;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeColor;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Radius;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;

/**
 * Renders a badge: a small, colored label used for statuses, counts and tags.
 * Inspired by https://reui.io/components/badge.
 */
class Badge
{
    /**
     * The color of the badge: 'default', 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light' or 'dark' (any other value is added as a 'badge-{color}' CSS class; null renders no color class).
     */
    public ?BadgeColor $color = BadgeColor::Default;
    /**
     * The visual style of the badge: 'light', 'solid', 'outline' or 'dot'; when null, it uses the badge variant of the current CRUD controller ('light' by default).
     */
    public ?BadgeVariant $variant = null;
    /**
     * The name of the optional icon displayed before the label.
     */
    public ?string $icon = null;
    /**
     * The name of the optional icon displayed after the label.
     */
    public ?string $endIcon = null;
    /**
     * Whether to display a button to dismiss the badge (it has no behavior; add your own JavaScript code to handle it).
     */
    public bool $dismissible = false;
    /**
     * The size of the badge: 'sm', 'md' or 'lg'.
     */
    public Size $size = Size::Medium;
    /**
     * The border radius of the badge: 'none', 'sm', 'md', 'lg' or 'full' (pill-shaped); when null, it inherits the border radius of the project.
     */
    public ?Radius $radius = null;
    private ?string $customColor = null;

    public function __construct(
        private readonly ComponentDefaultsProvider $componentDefaultsProvider,
    ) {
    }

    public function mount(string|BadgeColor|null $color = BadgeColor::Default, string|BadgeVariant|null $variant = null, string|Radius|null $radius = null, string|Size $size = Size::Medium): void
    {
        $this->resolveColor($color);
        $this->resolveVariant($variant);

        if ($radius instanceof Radius || null === $radius) {
            $this->radius = $radius;
        } else {
            $this->radius = Radius::tryFrom($radius);
        }

        // unknown sizes fall back to the base 'md' size, which emits no CSS class
        $this->size = \is_string($size) ? (Size::tryFrom($size) ?? Size::Medium) : $size;
    }

    public function getDefaultCssClass(): string
    {
        $cssClasses = ['badge'];

        if (null !== $this->color) {
            $cssClasses[] = $this->color->asCssClass();
        } elseif (null !== $this->customColor) {
            $cssClasses[] = 'badge-'.$this->customColor;
        }

        // the light variant is the base style of badges, so it emits no CSS class
        if (null !== $this->variant && BadgeVariant::Light !== $this->variant) {
            $cssClasses[] = $this->variant->asCssClass();
        }

        if (Size::Medium !== $this->size) {
            $cssClasses[] = 'badge-'.$this->size->value;
        }

        // when no radius is set, the badge inherits the project's default border-radius
        if (null !== $this->radius) {
            $cssClasses[] = $this->radius->cssClass();
        }

        return implode(' ', $cssClasses);
    }

    public function hasDot(): bool
    {
        return BadgeVariant::Dot === $this->variant;
    }

    private function resolveColor(string|BadgeColor|null $color): void
    {
        if ($color instanceof BadgeColor) {
            $this->color = $color;

            return;
        }

        // an empty/null color renders a badge with no color CSS class
        // (e.g. menu badges that only define an arbitrary inline style)
        if (null === $color || '' === $color) {
            $this->color = null;

            return;
        }

        $this->color = BadgeColor::tryFrom($color);
        if (null === $this->color) {
            // unknown colors (e.g. 'boolean-true', 'currency', 'language') are
            // passed through as a custom 'badge-{color}' CSS class
            $this->customColor = $color;
        }
    }

    private function resolveVariant(string|BadgeVariant|null $variant): void
    {
        if (null === $variant || '' === $variant) {
            $this->variant = $this->getDefaultBadgeVariant();

            return;
        }

        $resolvedVariant = $variant instanceof BadgeVariant ? $variant : BadgeVariant::tryFrom($variant);
        if (null !== $resolvedVariant && !$resolvedVariant->isColor()) {
            $this->variant = $resolvedVariant;

            return;
        }

        // in previous versions, the 'variant' prop defined the color of the badge
        $legacyColor = $variant instanceof BadgeVariant ? $variant->value : $variant;
        trigger_deprecation('easycorp/easyadmin-bundle', '5.7.0', 'Passing a color ("%s") in the "variant" prop of the <twig:ea:Badge> component is deprecated. Use the "color" prop instead (e.g. color="%s"); the "variant" prop now defines the visual style of the badge ("light", "solid", "outline" or "dot").', $legacyColor, $legacyColor);

        if (BadgeColor::Default === $this->color) {
            $this->resolveColor($legacyColor);
        }

        $this->variant = $this->getDefaultBadgeVariant();
    }

    private function getDefaultBadgeVariant(): BadgeVariant
    {
        return $this->componentDefaultsProvider->getDefaults()->getBadgeVariant() ?? BadgeVariant::Light;
    }
}
