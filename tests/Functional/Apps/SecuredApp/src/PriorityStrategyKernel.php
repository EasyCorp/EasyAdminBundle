<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp;

class PriorityStrategyKernel extends AbstractAccessDecisionStrategyKernel
{
    protected function getAccessDecisionManagerConfig(): array
    {
        return ['strategy' => 'priority'];
    }
}
