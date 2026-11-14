<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp;

class UnanimousStrategyKernel extends AbstractAccessDecisionStrategyKernel
{
    protected function getAccessDecisionManagerConfig(): array
    {
        return ['strategy' => 'unanimous'];
    }
}
