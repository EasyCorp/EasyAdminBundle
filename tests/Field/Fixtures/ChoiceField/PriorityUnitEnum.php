<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field\Fixtures\ChoiceField;

if (\PHP_VERSION_ID >= 80100) {
    enum PriorityUnitEnum
    {
        case High;
        case Normal;
        case Low;
    }
}
