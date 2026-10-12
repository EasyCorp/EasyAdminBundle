<?php

namespace EasyCorp\Bundle\EasyAdminBundle\UiKit;

use Symfony\UX\TwigComponent\Twig\PropsNode;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Token;

/**
 * Reads the documentation comments of the props (`## ...` above each prop of
 * `{% props %}`) and blocks (`{## ... ##}` above each block) of a component template.
 *
 * @internal
 */
final class ComponentDocumentationReader
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * Documentation comments need Twig 3.29 and TwigComponent 3.5.
     */
    public static function isSupported(): bool
    {
        /** @phpstan-ignore function.alreadyNarrowedType, function.alreadyNarrowedType */
        return method_exists(Token::class, 'getDocumentation') && method_exists(PropsNode::class, 'getPropDocumentation');
    }

    /**
     * @return array{
     *     props: list<array{name: string, type: string|null, description: string|null, default: string|null, required: bool}>,
     *     blocks: list<array{name: string, description: string|null}>,
     * }|null The props are empty for class-backed components; null when documentation comments aren't supported
     */
    public function read(string $templateName): ?array
    {
        if (!self::isSupported()) {
            return null;
        }

        $module = $this->twig->parse($this->twig->tokenize($this->twig->getLoader()->getSourceContext($templateName)));

        $props = [];
        $propsNode = $this->findFirst($module->getNode('body'), PropsNode::class);
        if ($propsNode instanceof PropsNode) {
            foreach ($propsNode->getAttribute('names') as $name) {
                [$type, $description] = $this->splitTypeAndDescription($propsNode->getPropDocumentation($name));
                $props[] = [
                    'name' => $name,
                    'type' => $type,
                    'description' => $description,
                    'default' => $propsNode->hasNode($name) ? $this->formatDefaultValue($propsNode->getNode($name)) : null,
                    'required' => !$propsNode->hasNode($name),
                ];
            }
        }

        $blocks = [];
        foreach ($module->getNode('blocks') as $name => $blockContainer) {
            // the block documentation is attached to the BlockNode, which Twig wraps in another node
            $blockNode = $this->findFirst($blockContainer, BlockNode::class);
            $blocks[] = [
                'name' => (string) $name,
                'description' => $blockNode?->getDocumentation(),
            ];
        }

        return ['props' => $props, 'blocks' => $blocks];
    }

    /**
     * Reads the props of a class-backed component: its public properties plus the
     * arguments of its mount() method, documented with their PHP docblocks.
     *
     * @param class-string $componentClass
     *
     * @return list<array{name: string, type: string|null, description: string|null, default: string|null, required: bool}>
     */
    public function readClassProps(string $componentClass): array
    {
        $class = new \ReflectionClass($componentClass);
        $mountArguments = [];
        $mountDocblock = $class->hasMethod('mount') ? $class->getMethod('mount')->getDocComment() : false;
        if ($class->hasMethod('mount')) {
            foreach ($class->getMethod('mount')->getParameters() as $argument) {
                $mountArguments[$argument->getName()] = $argument;
            }
        }

        $props = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            $mountArgument = $mountArguments[$name] ?? null;
            unset($mountArguments[$name]);

            // mount() usually accepts more types than the property (e.g. a string that is turned into an enum)
            $type = $mountArgument?->getType() ?? $property->getType();
            // but the property keeps the real default value (mount() arguments often default to null)
            if ($property->hasDefaultValue()) {
                $default = $this->formatPhpValue($property->getDefaultValue());
            } elseif (null !== $mountArgument && $mountArgument->isDefaultValueAvailable()) {
                $default = $this->formatPhpValue($mountArgument->getDefaultValue());
            } else {
                $default = null;
            }

            $props[] = [
                'name' => $name,
                'type' => null === $type ? null : $this->formatPhpType($type),
                'description' => $this->getDocblockDescription($property->getDocComment()),
                'default' => $default,
                'required' => null === $default,
            ];
        }

        // arguments of mount() that aren't stored in a public property are props too
        foreach ($mountArguments as $name => $argument) {
            $default = $argument->isDefaultValueAvailable() ? $this->formatPhpValue($argument->getDefaultValue()) : null;
            $props[] = [
                'name' => $name,
                'type' => null === $argument->getType() ? null : $this->formatPhpType($argument->getType()),
                'description' => $this->getParamDescription($mountDocblock, $name),
                'default' => $default,
                'required' => null === $default,
            ];
        }

        return $props;
    }

    /**
     * @template T of Node
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    private function findFirst(Node $node, string $class): ?Node
    {
        if ($node instanceof $class) {
            return $node;
        }

        foreach ($node as $child) {
            if (null !== $found = $this->findFirst($child, $class)) {
                return $found;
            }
        }

        return null;
    }

    private function getDocblockDescription(string|false $docblock): ?string
    {
        if (false === $docblock) {
            return null;
        }

        $lines = [];
        foreach (explode("\n", $docblock) as $line) {
            $line = trim(preg_replace('#^\s*(/\*\*|\*/|\*)#', '', $line) ?? '');
            $line = trim(preg_replace('#\*/$#', '', $line) ?? '');
            if (str_starts_with($line, '@')) {
                break;
            }
            if ('' !== $line) {
                $lines[] = $line;
            }
        }

        return [] === $lines ? null : implode(' ', $lines);
    }

    private function getParamDescription(string|false $docblock, string $argumentName): ?string
    {
        if (false === $docblock || 1 !== preg_match('#@param\s+\S+\s+\$'.preg_quote($argumentName, '#').'\s+([^\n]+)#', $docblock, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    /**
     * Uses short class names and the `string|null` notation of the template docs (instead of `?string`).
     */
    private function formatPhpType(\ReflectionType $type): string
    {
        $types = $type instanceof \ReflectionUnionType ? $type->getTypes() : [$type];
        $names = [];
        foreach ($types as $singleType) {
            if (!$singleType instanceof \ReflectionNamedType) {
                $names[] = (string) $singleType;
            } else {
                $names[] = $singleType->isBuiltin() ? $singleType->getName() : (new \ReflectionClass($singleType->getName()))->getShortName();
            }
        }

        if ($type->allowsNull() && !\in_array('null', $names, true) && !\in_array('mixed', $names, true)) {
            $names[] = 'null';
        }

        return implode('|', $names);
    }

    /**
     * Formats values the way they are written in Twig (e.g. a backed enum shows the
     * string or integer that you pass to the component).
     */
    private function formatPhpValue(mixed $value): string
    {
        return match (true) {
            null === $value => 'null',
            \is_bool($value) => $value ? 'true' : 'false',
            \is_string($value) => sprintf("'%s'", $value),
            \is_int($value), \is_float($value) => (string) $value,
            $value instanceof \BackedEnum => $this->formatPhpValue($value->value),
            $value instanceof \UnitEnum => (new \ReflectionClass($value))->getShortName().'::'.$value->name,
            [] === $value => '{}',
            default => '…',
        };
    }

    /**
     * The first word of a prop documentation is its type (e.g. "'sm'|'md' The size of ...").
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function splitTypeAndDescription(?string $documentation): array
    {
        if (null === $documentation) {
            return [null, null];
        }

        $parts = preg_split('/\s+/', trim($documentation), 2);

        return [$parts[0], $parts[1] ?? null];
    }

    private function formatDefaultValue(Node $value): string
    {
        if ($value instanceof ArrayExpression && 0 === \count($value->getKeyValuePairs())) {
            return '{}';
        }

        if (!$value instanceof ConstantExpression) {
            return '…';
        }

        return match (true) {
            null === $value->getAttribute('value') => 'null',
            \is_bool($value->getAttribute('value')) => $value->getAttribute('value') ? 'true' : 'false',
            \is_string($value->getAttribute('value')) => sprintf("'%s'", $value->getAttribute('value')),
            default => (string) $value->getAttribute('value'),
        };
    }
}
