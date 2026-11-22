<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component\Fixtures\StringOption;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Rendering tests for the <twig:ea:EmptyState> component.
 */
class EmptyStateTest extends AbstractFieldFunctionalTestCase
{
    public function testDefaultMessage(): void
    {
        $crawler = $this->renderEmptyState('<twig:ea:EmptyState />');

        self::assertSame('ea-empty-state', $crawler->filter('.ea-empty-state')->attr('class'));
        self::assertSame('No data to display', $crawler->filter('.ea-empty-state-message')->text());
        self::assertCount(0, $crawler->filter('.ea-empty-state-title'));
    }

    public function testErrorStatusAsString(): void
    {
        $crawler = $this->renderEmptyState('<twig:ea:EmptyState status="error" />');

        self::assertSame('ea-empty-state ea-empty-state-error', $crawler->filter('.ea-empty-state')->attr('class'));
        self::assertSame('This content could not be loaded.', $crawler->filter('.ea-empty-state-message')->text());
    }

    public function testErrorStatusAsEnumCase(): void
    {
        $crawler = $this->renderEmptyState('<twig:ea:EmptyState :status="status" />', ['status' => StringOption::Error]);

        self::assertSame('ea-empty-state ea-empty-state-error', $crawler->filter('.ea-empty-state')->attr('class'));
    }

    public function testTitleIsDisplayedAboveTheMessage(): void
    {
        $crawler = $this->renderEmptyState('<twig:ea:EmptyState title="No orders yet" message="Orders appear here." />');

        self::assertCount(1, $crawler->filter('.ea-empty-state > .ea-empty-state-icon + .ea-empty-state-title + .ea-empty-state-message'));
        self::assertSame('No orders yet', $crawler->filter('.ea-empty-state-title')->text());
        self::assertSame('Orders appear here.', $crawler->filter('.ea-empty-state-message')->text());
    }

    public function testTitleWithoutMessageDoesNotDisplayTheDefaultMessage(): void
    {
        $crawler = $this->renderEmptyState('<twig:ea:EmptyState title="No results" />');

        self::assertSame('No results', $crawler->filter('.ea-empty-state-title')->text());
        self::assertCount(0, $crawler->filter('.ea-empty-state-message'));
    }

    public function testTitleAndMessageBlocksCanContainHtml(): void
    {
        $crawler = $this->renderEmptyState('<twig:ea:EmptyState title="Ignored" message="Ignored"><twig:block name="title">No <strong>invoices</strong></twig:block><twig:block name="message">Read the <a href="#">guide</a>.</twig:block></twig:ea:EmptyState>');

        self::assertSame('No <strong>invoices</strong>', $crawler->filter('.ea-empty-state-title')->html());
        self::assertSame('Read the <a href="#">guide</a>.', $crawler->filter('.ea-empty-state-message')->html());
    }

    public function testPropsAreEscaped(): void
    {
        $crawler = $this->renderEmptyState('<twig:ea:EmptyState title="<b>Title</b>" message="<i>Message</i>" />');

        self::assertSame('<b>Title</b>', $crawler->filter('.ea-empty-state-title')->text());
        self::assertSame('<i>Message</i>', $crawler->filter('.ea-empty-state-message')->text());
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderEmptyState(string $template, array $context = []): Crawler
    {
        return new Crawler(static::getContainer()->get('twig')->createTemplate($template)->render($context));
    }
}
