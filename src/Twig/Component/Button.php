<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

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
     * The color of the button: 'default', 'primary', 'success', 'warning', 'danger' or 'info' (any other value is added as a 'btn-{variant}' CSS class).
     */
    public ?ButtonVariant $variant = ButtonVariant::Default;
    /**
     * Whether to remove the background and borders of the button (useful for icon-only buttons).
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

    public function mount(string|ButtonVariant|null $variant = null, string|ButtonElement|null $htmlElement = null, string|ButtonType|null $type = null, ?string $method = null, string|Size $size = Size::Medium): void
    {
        if ($variant instanceof ButtonVariant) {
            $this->variant = $variant;
        } elseif (null === $variant || '' === $variant) {
            $this->variant = ButtonVariant::Default;
        } else {
            $resolved = ButtonVariant::tryFrom($variant);
            $this->variant = $resolved;
            if (null === $resolved) {
                // unknown variants are passed through as a custom 'btn-{variant}' CSS class
                $this->customVariant = $variant;
            }
        }

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

        if (null !== $this->variant) {
            $cssClasses[] = $this->variant->asCssClass();
        } elseif (null !== $this->customVariant) {
            $cssClasses[] = 'btn-'.$this->customVariant;
        }

        if (Size::Medium !== $this->size) {
            $cssClasses[] = 'btn-'.$this->size->value;
        }

        if ($this->isInvisible) {
            $cssClasses[] = 'btn-invisible';
        }

        if ($this->isBlock) {
            $cssClasses[] = 'btn-block';
        }

        if ($this->inactive) {
            $cssClasses[] = 'disabled';
        }

        return implode(' ', $cssClasses);
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
}
