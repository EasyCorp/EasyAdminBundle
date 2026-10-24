<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;

/**
 * The default options of the Twig components configured in the dashboard or in
 * a CRUD controller (e.g. with Crud::setBadgeVariant()). Components use them when
 * the template doesn't define those options.
 */
final readonly class ComponentDefaultsDto
{
    public function __construct(
        private ?BadgeVariant $badgeVariant = null,
    ) {
    }

    public function getBadgeVariant(): ?BadgeVariant
    {
        return $this->badgeVariant;
    }

    public function withBadgeVariant(?BadgeVariant $badgeVariant): self
    {
        return new self(badgeVariant: $badgeVariant);
    }
}
