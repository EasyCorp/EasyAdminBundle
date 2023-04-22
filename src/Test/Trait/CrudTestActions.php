<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

use function PHPUnit\Framework\assertCount;

trait CrudTestActions
{
    protected function clickOnIndexGlobalAction(string $globalAction): void
    {
        $crawler = $this->client->getCrawler();
        $action = $crawler->filter(sprintf('.global-actions .action-%s', $globalAction));

        assertCount(1, $action, sprintf('There is no action %s in the page', $globalAction));

        $this->client->click($action->link());
    }
}
