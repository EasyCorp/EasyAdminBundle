<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AdminRoute;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\I18nKernel;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Checks the route that serves the contents of widgets when the admin routes are localized.
 */
class I18nWidgetRouteTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return I18nKernel::class;
    }

    #[TestWith(['en'])]
    #[TestWith(['ja'])]
    #[TestWith(['es'])]
    public function testWidgetRouteHasLocaleVariants(string $locale): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');

        $route = $router->getRouteCollection()->get('admin_widget.'.$locale);
        $this->assertNotNull($route);
        $this->assertSame('/'.$locale.'/admin/_widget/{widgetName}', $route->getPath());
        $this->assertSame($locale, $route->getDefault('_locale'));
        $this->assertSame(['GET'], $route->getMethods());

        $this->assertSame('/'.$locale.'/admin/_widget/widget-1', $router->generate('admin_widget', ['widgetName' => 'widget-1', '_locale' => $locale]));

        $client->request('GET', '/'.$locale.'/admin/_widget/missing-widget');
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('dashboard has no widget called', (string) $client->getResponse()->getContent());
    }
}
