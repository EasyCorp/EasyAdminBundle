<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * What a widget knows when it's rendered. It's passed to the closures that
 * compute the data of widgets and to their templates (as the 'context' variable).
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class WidgetContext
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly Request $request,
        private readonly ?UserInterface $user,
        private readonly string $dashboardControllerFqcn,
        private readonly WidgetDto $widget,
        private readonly array $attributes = [],
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getDashboardControllerFqcn(): string
    {
        return $this->dashboardControllerFqcn;
    }

    public function getWidget(): WidgetDto
    {
        return $this->widget;
    }

    /**
     * The page-wide state (e.g. a date range) provided by the services
     * that implement WidgetContextProviderInterface.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    /**
     * Returns the given value or, if it's a closure or an invokable object,
     * the result of calling it. Strings and arrays are never called, even
     * when they are the name of a function or a method.
     *
     * The context is passed as the first argument, unless the callable can't
     * receive it (e.g. a first-class callable such as '$this->countOrders(...)').
     */
    public function resolve(mixed $valueOrCallable): mixed
    {
        if (!$valueOrCallable instanceof \Closure && !(\is_object($valueOrCallable) && \is_callable($valueOrCallable))) {
            return $valueOrCallable;
        }

        $reflection = $valueOrCallable instanceof \Closure ? new \ReflectionFunction($valueOrCallable) : new \ReflectionMethod($valueOrCallable, '__invoke');
        $firstParameter = $reflection->getParameters()[0] ?? null;

        if (null === $firstParameter || !self::acceptsContext($firstParameter->getType())) {
            return $valueOrCallable();
        }

        return $valueOrCallable($this);
    }

    private static function acceptsContext(?\ReflectionType $type): bool
    {
        if (null === $type) {
            return true;
        }

        if ($type instanceof \ReflectionNamedType) {
            return \in_array($type->getName(), ['mixed', 'object', self::class], true);
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if (self::acceptsContext($innerType)) {
                    return true;
                }
            }
        }

        return false;
    }
}
