<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequiresAdminRole extends IsGranted
{
    public function __construct()
    {
        parent::__construct('ROLE_ADMIN');
    }
}
