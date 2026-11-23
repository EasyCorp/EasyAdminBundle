<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

enum FieldTestStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
