<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms between \BcMath\Number objects and string values.
 */
class StringToBcMathNumberTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \BcMath\Number) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of "%s", got "%s".', \BcMath\Number::class, get_debug_type($value)));
        }

        return (string) $value;
    }

    public function reverseTransform(mixed $value): ?\BcMath\Number
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return new \BcMath\Number($value);
    }
}
