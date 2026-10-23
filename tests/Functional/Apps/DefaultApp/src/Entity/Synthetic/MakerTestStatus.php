<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

enum MakerTestStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
