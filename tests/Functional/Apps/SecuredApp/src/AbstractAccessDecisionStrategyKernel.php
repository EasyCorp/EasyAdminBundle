<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * SecuredApp with a non-default access decision strategy. The strategy decides how the
 * votes on the attributes of a multi-attribute access_control rule are combined, so these
 * kernels check that EasyAdmin decides those rules like Symfony does.
 */
abstract class AbstractAccessDecisionStrategyKernel extends Kernel
{
    /**
     * @return array<string, mixed> the 'security.access_decision_manager' config
     */
    abstract protected function getAccessDecisionManagerConfig(): array;

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/secured_'.$this->getAccessDecisionManagerConfig()['strategy'].'/var/'.$this->environment.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/secured_'.$this->getAccessDecisionManagerConfig()['strategy'].'/var/'.$this->environment.'/log';
    }

    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader): void
    {
        parent::configureContainer($containerBuilder, $loader);

        $containerBuilder->loadFromExtension('security', [
            'access_decision_manager' => $this->getAccessDecisionManagerConfig(),
        ]);
    }
}
