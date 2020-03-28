<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @method FieldInterface   get(string $propertyName)
 * @method bool             has(string $propertyName)
 * @method FieldInterface[] all()
 */
final class Fields extends ParameterBag
{
    public function __construct($fieldDtos)
    {
        $fieldsByProperty = [];
        /** @var FieldInterface $field */
        foreach ($fieldDtos as $field) {
            $fieldsByProperty[$field->getName()] = $field;
        }

        parent::__construct($fieldsByProperty);
    }

    public static function new($fieldsDto): self
    {
        return new self($fieldsDto);
    }
}
