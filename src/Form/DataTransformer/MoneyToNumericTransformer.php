<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DataTransformer;

use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms between Money\Money objects and numeric (int) values.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MoneyToNumericTransformer implements DataTransformerInterface
{
    public function __construct(private readonly string $currencyCode)
    {
    }

    public function transform(mixed $value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Money) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of "%s", got "%s".', Money::class, get_debug_type($value)));
        }

        return (int) $value->getAmount();
    }

    public function reverseTransform(mixed $value): ?Money
    {
        if (null === $value) {
            return null;
        }

        return new Money((string) $value, new Currency($this->currencyCode));
    }
}
