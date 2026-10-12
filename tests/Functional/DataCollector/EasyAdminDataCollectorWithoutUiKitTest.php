<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\DataCollector;

use EasyCorp\Bundle\EasyAdminBundle\DataCollector\EasyAdminDataCollector;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * This app always runs without debug mode and doesn't set the parameter that enables
 * the UI Kit, so the UI Kit routes don't exist and the profiler panel can't link to it.
 */
class EasyAdminDataCollectorWithoutUiKitTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testUiKitUrlIsNullWhenTheUiKitIsDisabled(): void
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('GET', '/admin');

        /** @var EasyAdminDataCollector $collector */
        $collector = $client->getProfile()->getCollector('easyadmin');

        $this->assertTrue($collector->isEasyAdminRequest());
        $this->assertNull($collector->getData()['ui_kit_url']);
    }
}
