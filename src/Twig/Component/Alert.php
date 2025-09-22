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

    public function mount(string|AlertVariant $variant): void
    {
        try {
            $this->variant = \is_string($variant) ? AlertVariant::from($variant) : $variant;
        } catch (\ValueError) {
            throw new \InvalidArgumentException(sprintf('The alert variant "%s" is not valid. Valid values are: %s', $variant, implode(', ', array_map(static fn (AlertVariant $variant): string => $variant->value, AlertVariant::cases()))));
        }
    }

    public function getDefaultCssClass(): string
    {
        $cssClass = sprintf('alert %s', $this->variant->asBootstrapCssClass());
        if ($this->withDismissButton) {
            $cssClass .= ' alert-dismissible';
        }

        return $cssClass;
    }
}
