<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp;

class ConsensusStrategyKernel extends AbstractAccessDecisionStrategyKernel
{
    protected function getAccessDecisionManagerConfig(): array
    {
        return ['strategy' => 'consensus', 'allow_if_equal_granted_denied' => false];
    }
}
