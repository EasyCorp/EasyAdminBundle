<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Fixtures\ChoiceField;

if (\PHP_VERSION_ID >= 80100) {
    enum StatusBackedEnum: string
    {
        case Draft = 'draft';
        case Published = 'published';
        case Deleted = 'deleted';
    }
}
