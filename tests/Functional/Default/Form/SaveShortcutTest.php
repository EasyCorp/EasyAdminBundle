<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Form;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Form\SaveShortcutDisabledCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use Symfony\Component\DomCrawler\Crawler;

class SaveShortcutTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return BlogPostCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    /**
     * @dataProvider providePageNames
     */
    public function testSaveShortcutIsEnabledByDefault(string $pageName, string $otherSaveActionName): void
    {
        $crawler = $this->requestFormPage($pageName, BlogPostCrudController::class);

        $this->assertSame('true', $crawler->filter(sprintf('form.ea-%s-form', $pageName))->attr('data-ea-save-shortcut'));
        $this->assertSame('Control+S Meta+S', $this->findActionButton($crawler, Action::SAVE_AND_RETURN)->attr('aria-keyshortcuts'));
        $this->assertNull($this->findActionButton($crawler, $otherSaveActionName)->attr('aria-keyshortcuts'));
    }

    /**
     * @dataProvider providePageNames
     */
    public function testSaveShortcutCanBeDisabled(string $pageName, string $otherSaveActionName): void
    {
        $crawler = $this->requestFormPage($pageName, SaveShortcutDisabledCrudController::class);

        $this->assertSame('false', $crawler->filter(sprintf('form.ea-%s-form', $pageName))->attr('data-ea-save-shortcut'));
        $this->assertNull($this->findActionButton($crawler, Action::SAVE_AND_RETURN)->attr('aria-keyshortcuts'));
        $this->assertNull($this->findActionButton($crawler, $otherSaveActionName)->attr('aria-keyshortcuts'));
    }

    public static function providePageNames(): iterable
    {
        yield [Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER];
        yield [Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE];
    }

    private function requestFormPage(string $pageName, string $controllerFqcn): Crawler
    {
        $url = Crud::PAGE_NEW === $pageName
            ? $this->generateNewFormUrl(controllerFqcn: $controllerFqcn)
            : $this->generateEditFormUrl($this->entityManager->getRepository(BlogPost::class)->findOneBy([])->getId(), controllerFqcn: $controllerFqcn);

        $crawler = $this->client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    private function findActionButton(Crawler $crawler, string $actionName): Crawler
    {
        $button = $crawler->filter(sprintf('button[data-action-name="%s"]', $actionName));
        $this->assertCount(1, $button);

        return $button;
    }
}
