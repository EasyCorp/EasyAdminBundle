<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\AlertVariant;

/**
 * Highlights important messages that require the user's attention, such as notifications and flash messages.
 * Inspired by https://primer.style/components/banner.
 */
class Alert
{
    public AlertVariant $variant = AlertVariant::Info;
    public bool $withDismissButton = false;
    private ?string $customVariant = null;

    public function mount(string|AlertVariant $variant): void
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
