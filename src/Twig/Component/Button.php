<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonColor;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonElement;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonType;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\PreMount;

/**
 * Renders a button as a <button>, <a> or <form> element, with optional
 * leading/trailing icons and a label.
 */
class Button
{
    /**
     * The color of the button: 'default' (a neutral gray), 'primary' (the primary color of the theme), 'success', 'warning', 'danger' or 'info' (any other value is added as a 'btn-{color}' CSS class).
     */
    public ?ButtonColor $color = ButtonColor::Default;
    /**
     * The visual style of the button: 'solid', 'outline', 'light', 'ghost' or 'link'; when null, it depends on the color ('solid' for primary buttons, 'outline' for default buttons and 'light' for the other colors).
     */
    public ?ButtonVariant $variant = null;
    /**
     * Deprecated: use variant="ghost" instead.
     */
    public bool $isInvisible = false;
    /**
     * Whether the button takes the full width of its container.
     */
    public bool $isBlock = false;
    /**
     * The size of the button: 'sm', 'md' or 'lg'.
     */
    public Size $size = Size::Medium;
    /**
     * The name of the optional icon displayed next to the label.
     */
    public ?string $icon = null;
    /**
     * Whether to display the icon after the label instead of before it.
     */
    public bool $withTrailingIcon = false;
    /**
     * Whether to render the button in a disabled state.
     */
    public bool $inactive = false;
    /**
     * The HTML element used to render the button: 'button', 'a' (a link that looks like a button) or 'form' (a form that submits to the action URL).
     */
    public ButtonElement $htmlElement = ButtonElement::Button;
    /**
     * The URL of the link (only when htmlElement is 'a').
     */
    public ?string $href = null;
    /**
     * The URL the form is submitted to (only when htmlElement is 'form').
     */
    public ?string $action = null;
    /**
     * The HTTP method of the form (only when htmlElement is 'form'); methods other than GET and POST are sent as POST with a '_method' field.
     */
    public string $method = 'POST';
    /**
     * The type attribute of the <button> element: 'submit' or 'button'.
     */
    public ButtonType $type = ButtonType::Submit;
    /**
     * The name attribute of the <button> element (useful to tell apart several submit buttons of the same form).
     */
    public ?string $name = null;
    /**
     * The value attribute of the <button> element.
     */
    public ?string $value = null;
    private ?string $customColor = null;
    private ?string $customVariant = null;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * BC layer: transparently migrates the deprecated 'htmlAttributes' prop into
     * the native attributes system (remove in EasyAdmin 6.0).
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[PreMount]
    public function migrateHtmlAttributes(array $data): array
    {
        if ((bool) ($data['isInvisible'] ?? false)) {
            trigger_deprecation('easycorp/easyadmin-bundle', '5.9.0', 'The "isInvisible" prop of the <twig:ea:Button> component is deprecated, use variant="ghost" instead.');
        }

        if (!\array_key_exists('htmlAttributes', $data)) {
            return $data;
        }

        trigger_deprecation('easycorp/easyadmin-bundle', '5.2.0', 'The "htmlAttributes" prop of the <twig:ea:Button> component is deprecated, pass the attributes directly on the component (or use the "{{ ... }}" spread syntax for dynamic maps) instead.');

        $htmlAttributes = $data['htmlAttributes'];
        unset($data['htmlAttributes']);

        // the old template silently ignored non-iterable values, keep that behavior
        if (!is_iterable($htmlAttributes)) {
            return $data;
        }

        foreach ($htmlAttributes as $attrName => $attrValue) {
            // attributes passed directly on the component win over the map ones
            if (\array_key_exists($attrName, $data)) {
                continue;
            }

            if ($attrValue instanceof TranslatableInterface) {
                $attrValue = $attrValue->trans($this->translator);
            }

            $data[$attrName] = $attrValue ?? '';
        }

        return $data;
    }

    public function mount(string|ButtonVariant|null $variant = null, string|ButtonElement|null $htmlElement = null, string|ButtonType|null $type = null, ?string $method = null, string|Size $size = Size::Medium, string|ButtonColor|null $color = null): void
    {
        $this->resolveColor($color);
        $this->resolveVariant($variant, null !== $color && '' !== $color);

        if ($htmlElement instanceof ButtonElement) {
            $this->htmlElement = $htmlElement;
        } else {
            $this->htmlElement = ButtonElement::tryFrom($htmlElement ?? '') ?? ButtonElement::Button;
        }

        if ($type instanceof ButtonType) {
            $this->type = $type;
        } else {
            $this->type = ButtonType::tryFrom($type ?? '') ?? ButtonType::Submit;
        }

        $this->method = null === $method || '' === $method ? 'POST' : strtoupper($method);

        // unknown sizes fall back to the base 'md' size, which emits no CSS class
        $this->size = \is_string($size) ? (Size::tryFrom($size) ?? Size::Medium) : $size;
    }

    public function getDefaultCssClass(): string
    {
        $cssClasses = ['btn'];

        if (null !== $this->color) {
            $cssClasses[] = $this->color->asCssClass();
        } elseif (null !== $this->customColor) {
            $cssClasses[] = 'btn-'.$this->customColor;
        }

        if (null !== $this->customVariant) {
            $cssClasses[] = 'btn-'.$this->customVariant;
        } else {
            $cssClasses[] = $this->getResolvedVariant()->asCssClass();
        }

        if (Size::Medium !== $this->size) {
            $cssClasses[] = 'btn-'.$this->size->value;
        }

        if ($this->isBlock) {
            $cssClasses[] = 'btn-block';
        }

        if ($this->inactive) {
            $cssClasses[] = 'disabled';
        }

        return implode(' ', $cssClasses);
    }

    /**
     * The variant used to render the button: the one passed to the component or,
     * when there's none, the default variant of its color.
     */
    public function getResolvedVariant(): ButtonVariant
    {
        if ($this->isInvisible) {
            return ButtonVariant::Ghost;
        }

        return $this->variant ?? ($this->color ?? ButtonColor::Default)->getDefaultVariant();
    }

    // HTML forms only support GET and POST methods; other methods (PUT, DELETE, etc.)
    // require submitting a POST form with the real method in a '_method' hidden field
    public function usesMethodOverride(): bool
    {
        return !\in_array($this->method, ['GET', 'POST'], true);
    }

    public function getFormMethod(): string
    {
        return $this->usesMethodOverride() ? 'POST' : $this->method;
    }

    private function resolveColor(string|ButtonColor|null $color): void
    {
        if ($color instanceof ButtonColor) {
            $this->color = $color;

            return;
        }

        if (null === $color || '' === $color) {
            $this->color = ButtonColor::Default;

            return;
        }

        // 'secondary' is the CSS class of the default color, so it's accepted as an alias
        $this->color = 'secondary' === $color ? ButtonColor::Default : ButtonColor::tryFrom($color);
        if (null === $this->color) {
            $this->customColor = $color;
        }
    }

    private function resolveVariant(string|ButtonVariant|null $variant, bool $hasExplicitColor): void
    {
        if (null === $variant || '' === $variant) {
            $this->variant = null;

            return;
        }

        $resolvedVariant = $variant instanceof ButtonVariant ? $variant : ButtonVariant::tryFrom($variant);
        if (null !== $resolvedVariant && !$resolvedVariant->isColor()) {
            $this->variant = $resolvedVariant;

            return;
        }

        $legacyColor = $variant instanceof ButtonVariant ? $variant->value : $variant;
        if (null === $resolvedVariant && 'secondary' !== $legacyColor) {
            $this->variant = null;
            $this->customVariant = $variant instanceof ButtonVariant ? null : $variant;

            return;
        }

        // in previous versions, the 'variant' prop defined the color of the button
        trigger_deprecation('easycorp/easyadmin-bundle', '5.9.0', 'Passing a color ("%s") in the "variant" prop of the <twig:ea:Button> component is deprecated. Use the "color" prop instead (e.g. color="%s"); the "variant" prop now defines the visual style of the button ("solid", "outline", "light", "ghost" or "link").', $legacyColor, $legacyColor);

        if (!$hasExplicitColor) {
            $this->resolveColor($legacyColor);
        }

        $this->variant = null;
    }
}
