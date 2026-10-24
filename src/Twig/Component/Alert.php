<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\AlertVariant;

/**
 * Highlights important messages that require the user's attention, such as notifications and flash messages.
 * Inspired by https://primer.style/components/banner and https://ui.shadcn.com/docs/components/alert.
 */
class Alert
{
    /**
     * The color and meaning of the alert: the usual Bootstrap values ('info', 'success', 'warning', 'danger', etc.; 'notice' and 'error' are aliases of 'info' and 'danger'), and any other value is added as an 'alert-{variant}' CSS class.
     */
    public AlertVariant $variant = AlertVariant::Info;
    /**
     * Whether to display a button to close the alert.
     */
    public bool $withDismissButton = false;
    /**
     * The name of the optional icon displayed before the contents.
     */
    public ?string $icon = null;
    /**
     * The optional title displayed above the message (use the title block for custom HTML contents).
     */
    public ?string $title = null;
    private ?string $customVariant = null;

    public function mount(string|AlertVariant $variant = AlertVariant::Info): void
    {
        if (\is_string($variant)) {
            $resolved = AlertVariant::tryFrom($variant);
            $this->variant = $resolved ?? AlertVariant::Info;
            if (null === $resolved) {
                $this->customVariant = $variant;
            }
        } else {
            $this->variant = $variant;
        }
    }

    public function getDefaultCssClass(): string
    {
        $cssClass = sprintf('alert %s', $this->variant->asBootstrapCssClass());
        if (null !== $this->customVariant) {
            $cssClass .= sprintf(' alert-%s', $this->customVariant);
        }
        if ($this->withDismissButton) {
            $cssClass .= ' alert-dismissible';
        }

        return $cssClass;
    }
}
