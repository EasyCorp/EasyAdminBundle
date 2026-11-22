<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsErrorsDashboardController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Checks what happens when the data of a widget can't be computed. The test apps
 * don't run in debug mode, so the debug mode is checked with a separate kernel.
 */
class WidgetsErrorsTest extends WebTestCase
{
    public function testFailingWidgetDisplaysAnErrorWithoutBreakingThePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-errors-admin');

        $this->assertResponseIsSuccessful();

        $failingWidget = $crawler->filter('.ea-widget[data-ea-widget="failing-stat"]');
        $this->assertSame('/widgets-errors-admin/_widget/failing-stat', $failingWidget->attr('data-ea-widget-url'));
        $this->assertNull($failingWidget->attr('data-ea-widget-lazy'));
        $this->assertErrorState($failingWidget->filter('[data-ea-widget-body]'));
        $this->assertCount(1, $failingWidget->filter('template[data-ea-widget-error-template]'));
        $this->assertStringNotContainsString(WidgetsErrorsDashboardController::ERROR_MESSAGE, (string) $client->getResponse()->getContent());

        $workingWidget = $crawler->filter('.ea-widget[data-ea-widget="working-stat"]');
        $this->assertNull($workingWidget->attr('data-ea-widget-url'));
        $this->assertSame('Working value', $workingWidget->filter('[data-ea-widget-body] .ea-stat-value')->text());
        $this->assertCount(0, $workingWidget->filter('.ea-empty-state-error'));

        $this->assertErrorState($crawler->filter('.ea-widget[data-ea-widget="failing-cached-stat"] [data-ea-widget-body]'));

        // the script is needed to load the contents again when clicking on the retry button
        $this->assertCount(1, $crawler->filter('script[src*="/widgets."]'));
    }

    public function testFragmentOfFailingWidgetReturnsTheErrorState(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-errors-admin/_widget/failing-stat');

        // the error status tells the JavaScript code that the contents couldn't be loaded
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertResponseHeaderSame('X-EasyAdmin-Widget', 'failing-stat');
        $this->assertErrorState($crawler->filter('body'));
        $this->assertStringNotContainsString(WidgetsErrorsDashboardController::ERROR_MESSAGE, (string) $client->getResponse()->getContent());
    }

    public function testFailingWidgetThrowsTheExceptionInDebugMode(): void
    {
        $client = static::createClient(['environment' => 'test', 'debug' => true]);
        $this->assertTrue(static::getContainer()->getParameter('kernel.debug'));

        $client->request('GET', '/widgets-errors-admin');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        $client->request('GET', '/widgets-errors-admin/_widget/failing-stat');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        // the exception is not turned into the error state of the widget
        $this->assertFalse($client->getResponse()->headers->has('X-EasyAdmin-Widget'));

        $client->request('GET', '/widgets-errors-admin/_widget/working-stat');
        $this->assertResponseIsSuccessful();
    }

    public function testExceptionOfFailingWidgetIsNotHiddenInDebugMode(): void
    {
        $client = static::createClient(['environment' => 'test', 'debug' => true]);
        $client->catchExceptions(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(WidgetsErrorsDashboardController::ERROR_MESSAGE);

        $client->request('GET', '/widgets-errors-admin/_widget/failing-stat');
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return ($options['debug'] ?? false) ? new DebugKernel() : parent::createKernel($options);
    }

    private function assertErrorState(Crawler $container): void
    {
        $errorState = $container->filter('.ea-empty-state.ea-empty-state-error');
        $this->assertCount(1, $errorState);
        $this->assertSame('This content could not be loaded.', $errorState->filter('.ea-empty-state-message')->text());
        $this->assertSame('Try again', $errorState->filter('button[data-ea-widget-retry]')->text());
    }
}
