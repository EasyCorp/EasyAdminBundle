<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides page-wide state (e.g. a date range, the current tenant) to all the
 * widgets of a dashboard. The attributes returned by all providers are merged
 * and passed to widgets via WidgetContext.
 *
 * The attributes are also part of the cache key of cached widgets, so their
 * values must be scalars, arrays, enums or objects that can be serialized.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface WidgetContextProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getAttributes(Request $request): array;
}
