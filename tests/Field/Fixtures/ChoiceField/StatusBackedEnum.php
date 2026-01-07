<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Fixtures\ChoiceField;

enum StatusBackedEnum: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft label',
            self::Published => 'Published label',
            self::Deleted => 'Deleted label',
        };
    }
}
