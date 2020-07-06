<?php

namespace EasyCorp\Bundle\EasyAdminBundle\PropertyInfo;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

/**
 * @author Lukas Lücke <lukas@luecke.me>
 */
class NestedExtractor implements PropertyTypeExtractorInterface
{
    public const SKIP_CIRCULAR = 'nested_extractor_skip_circular';

    private $infoExtractor;

    public function __construct(PropertyTypeExtractorInterface $infoExtractor)
    {
        $this->infoExtractor = $infoExtractor;
    }

    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        if ($context[self::SKIP_CIRCULAR] ?? false) {
            return null;
        }
        $context[self::SKIP_CIRCULAR] = true;

        if (strpos($property, '.') === -1) {
            return null;
        }

        $currentClass = $class;
        $types = null;
        foreach (explode('.', $property) as $part) {
            $types = $this->infoExtractor->getTypes($currentClass, $part, $context);
            if ($types === null) {
                return null;
            }

            $currentClass = $types[0]->getClassName();
            if ($currentClass === null) {
                return null;
            }
        }

        return $types;
    }
}

