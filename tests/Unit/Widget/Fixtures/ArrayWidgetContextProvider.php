<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetContextProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class ArrayWidgetContextProvider implements WidgetContextProviderInterface
{
    public int $calls = 0;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(public array $attributes)
    {
    }

    public function getAttributes(Request $request): array
    {
        ++$this->calls;

        return $this->attributes;
    }
}
