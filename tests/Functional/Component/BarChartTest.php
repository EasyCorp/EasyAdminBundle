<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Rendering tests for the <twig:ea:BarChart> component.
 */
class BarChartTest extends AbstractFieldFunctionalTestCase
{
    public function testAriaLabelNamesTheFigureAndTheDataTable(): void
    {
        $crawler = $this->renderBarChart('<twig:ea:BarChart :data="{Mon: 120, Tue: 95}" ariaLabel="Orders per day" />');

        $figure = $crawler->filter('figure.ea-bar-chart');
        self::assertSame('Orders per day', $figure->attr('aria-label'));
        self::assertSame('Orders per day', $figure->filter('table.visually-hidden caption')->text());
        self::assertSame('true', $figure->filter('.ea-bar-chart-plot')->attr('aria-hidden'));
    }

    public function testWithoutAriaLabel(): void
    {
        $crawler = $this->renderBarChart('<twig:ea:BarChart :data="{Mon: 120}" />');

        self::assertNull($crawler->filter('figure.ea-bar-chart')->attr('aria-label'));
        self::assertCount(0, $crawler->filter('caption'));
    }

    private function renderBarChart(string $template): Crawler
    {
        return new Crawler(static::getContainer()->get('twig')->createTemplate($template)->render());
    }
}
