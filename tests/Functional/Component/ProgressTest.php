<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Rendering tests for the <twig:ea:Progress> component.
 */
class ProgressTest extends AbstractFieldFunctionalTestCase
{
    public function testMaxIsThe100Percent(): void
    {
        $crawler = $this->renderProgress('<twig:ea:Progress :value="30" :max="120" ariaLabel="Monthly goal" />');

        $bar = $crawler->filter('progress.ea-progress-bar');
        self::assertSame('30', $bar->attr('value'));
        self::assertSame('120', $bar->attr('max'));
        self::assertSame('25%', $bar->attr('aria-valuetext'));
        self::assertSame('25%', $crawler->filter('.ea-progress-value')->text());
    }

    public function testValuesAboveMaxShowTheRealPercentage(): void
    {
        $crawler = $this->renderProgress('<twig:ea:Progress :value="1320" :max="1100" />');

        $bar = $crawler->filter('progress.ea-progress-bar');
        self::assertSame('1100', $bar->attr('value'));
        self::assertSame('120%', $bar->attr('aria-valuetext'));
    }

    public function testAriaLabelIsTheAccessibleNameWithoutVisibleLabel(): void
    {
        $crawler = $this->renderProgress('<twig:ea:Progress :value="64" ariaLabel="Profile completed" />');

        self::assertSame('ea-progress', $crawler->filter('.ea-progress')->attr('class'));
        self::assertSame('Profile completed', $crawler->filter('progress')->attr('aria-label'));
        self::assertNull($crawler->filter('progress')->attr('aria-labelledby'));
        self::assertCount(0, $crawler->filter('.ea-progress-header, .ea-progress-label'));
        // without a label, the percentage is displayed next to the bar
        self::assertCount(1, $crawler->filter('.ea-progress > .ea-progress-value'));
    }

    public function testVisibleLabelIsDisplayedInAHeaderAboveTheBar(): void
    {
        $crawler = $this->renderProgress('<twig:ea:Progress label="Storage" :value="7.2" :max="10" ariaLabel="Ignored" />');

        self::assertSame('ea-progress ea-progress-labelled', $crawler->filter('.ea-progress')->attr('class'));
        $header = $crawler->filter('.ea-progress > .ea-progress-header');
        self::assertSame('Storage', $header->filter('.ea-progress-label')->text());
        self::assertSame('72%', $header->filter('.ea-progress-value')->text());
        self::assertCount(1, $crawler->filter('.ea-progress-value'));

        $bar = $crawler->filter('progress');
        $labelId = $header->filter('.ea-progress-label')->attr('id');
        self::assertNotEmpty($labelId);
        self::assertSame($labelId, $bar->attr('aria-labelledby'));
        self::assertNull($bar->attr('aria-label'));
    }

    public function testLabelBlockCanContainHtml(): void
    {
        $crawler = $this->renderProgress('<twig:ea:Progress :value="50" :showValue="false"><twig:block name="label">Seats <small>(Team plan)</small></twig:block></twig:ea:Progress>');

        self::assertSame('Seats <small>(Team plan)</small>', $crawler->filter('.ea-progress-label')->html());
        self::assertSame($crawler->filter('.ea-progress-label')->attr('id'), $crawler->filter('progress')->attr('aria-labelledby'));
        self::assertCount(0, $crawler->filter('.ea-progress-value'));
    }

    public function testLabelPropIsEscaped(): void
    {
        $crawler = $this->renderProgress('<twig:ea:Progress label="<b>Storage</b>" />');

        self::assertSame('<b>Storage</b>', $crawler->filter('.ea-progress-label')->text());
    }

    private function renderProgress(string $template): Crawler
    {
        return new Crawler(static::getContainer()->get('twig')->createTemplate($template)->render());
    }
}
