<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\WidgetPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget\Fixtures\DummyWidget;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Translation\TranslatableMessage;

class WidgetTraitTest extends TestCase
{
    public function testDefaults(): void
    {
        $dto = DummyWidget::new()->getAsDto();

        $this->assertSame(DummyWidget::class, $dto->getWidgetFqcn());
        $this->assertSame('dummy', $dto->getType());
        $this->assertNull($dto->getName());
        $this->assertNull($dto->getLabel());
        $this->assertNull($dto->getIcon());
        $this->assertNull($dto->getHelp());
        $this->assertNull($dto->getLinkUrl());
        $this->assertNull($dto->getLinkLabel());
        $this->assertNull($dto->getPermission());
        $this->assertSame(4, $dto->getColumns());
        $this->assertSame(4, $dto->getDefaultColumns());
        $this->assertNull($dto->getRows());
        $this->assertFalse($dto->isLazy());
        $this->assertNull($dto->getCacheTtl());
        $this->assertFalse($dto->isCacheShared());
        $this->assertNull($dto->getRefreshInterval());
        $this->assertSame('', $dto->getCssClass());
        $this->assertNull($dto->getTemplateName());
        $this->assertSame('dummy.html.twig', $dto->getTemplatePath());
        $this->assertSame(WidgetPlacement::Grid, $dto->getPlacement());
        $this->assertFalse($dto->isRenderedWithoutCard());
        $this->assertTrue($dto->getCustomOptions()->isEmpty());
        $this->assertSame([], $dto->getData());
    }

    public function testSettersStoreTheirValuesInTheDto(): void
    {
        $permission = new Expression('is_granted("ROLE_ADMIN")');
        $dto = DummyWidget::new('Users')
            ->setName('total_users')
            ->setIcon('fa fa-users')
            ->setHelp('Help text')
            ->setLink('/admin/users', 'See all')
            ->setPermission($permission)
            ->setColumns(6)
            ->setRows(2)
            ->setLazy()
            ->setCache(300)
            ->setRefreshInterval(30)
            ->getAsDto();

        $this->assertSame('total_users', $dto->getName());
        $this->assertSame('Users', $dto->getLabel());
        $this->assertSame('fa fa-users', $dto->getIcon());
        $this->assertSame('Help text', $dto->getHelp());
        $this->assertSame('/admin/users', $dto->getLinkUrl());
        $this->assertSame('See all', $dto->getLinkLabel());
        $this->assertSame($permission, $dto->getPermission());
        $this->assertSame(6, $dto->getColumns());
        $this->assertSame(4, $dto->getDefaultColumns());
        $this->assertSame(2, $dto->getRows());
        $this->assertTrue($dto->isLazy());
        $this->assertSame(300, $dto->getCacheTtl());
        $this->assertFalse($dto->isCacheShared());
        $this->assertSame(30, $dto->getRefreshInterval());
    }

    public function testTranslatableTexts(): void
    {
        $label = new TranslatableMessage('widget.users');
        $help = new TranslatableMessage('widget.users_help');
        $linkLabel = new TranslatableMessage('widget.see_all');

        $dto = DummyWidget::new($label)->setHelp($help)->setLink('/admin/users', $linkLabel)->getAsDto();

        $this->assertSame($label, $dto->getLabel());
        $this->assertSame($help, $dto->getHelp());
        $this->assertSame($linkLabel, $dto->getLinkLabel());
    }

    public function testLinkWithoutLabel(): void
    {
        $dto = DummyWidget::new()->setLink('/admin/users')->getAsDto();

        $this->assertSame('/admin/users', $dto->getLinkUrl());
        $this->assertNull($dto->getLinkLabel());
    }

    public function testSetLazyFalseAndSharedCache(): void
    {
        $dto = DummyWidget::new()->setLazy()->setLazy(false)->setCache(60, true)->getAsDto();

        $this->assertFalse($dto->isLazy());
        $this->assertSame(60, $dto->getCacheTtl());
        $this->assertTrue($dto->isCacheShared());
    }

    #[TestWith([1])]
    #[TestWith([12])]
    public function testValidColumns(int $columns): void
    {
        $this->assertSame($columns, DummyWidget::new()->setColumns($columns)->getAsDto()->getColumns());
        $this->assertSame($columns, DummyWidget::new()->setDefaultColumns($columns)->getAsDto()->getColumns());
    }

    #[TestWith([0])]
    #[TestWith([13])]
    #[TestWith([-1])]
    public function testInvalidColumns(int $columns): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The number of columns of a widget must be between 1 and 12 (%d given).', $columns));

        DummyWidget::new()->setColumns($columns);
    }

    #[TestWith([0])]
    #[TestWith([13])]
    public function testInvalidDefaultColumns(int $columns): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DummyWidget::new()->setDefaultColumns($columns);
    }

    #[TestWith([1])]
    #[TestWith([6])]
    public function testValidRows(int $rows): void
    {
        $this->assertSame($rows, DummyWidget::new()->setRows($rows)->getAsDto()->getRows());
    }

    #[TestWith([0])]
    #[TestWith([7])]
    public function testInvalidRows(int $rows): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The number of rows of a widget must be between 1 and 6 (%d given).', $rows));

        DummyWidget::new()->setRows($rows);
    }

    public function testMinimumAndMaximumRefreshInterval(): void
    {
        $this->assertSame(10, DummyWidget::new()->setRefreshInterval(10)->getAsDto()->getRefreshInterval());
        $this->assertSame(86400, DummyWidget::new()->setRefreshInterval(86400)->getAsDto()->getRefreshInterval());
    }

    #[TestWith([86401])]
    #[TestWith([2592000])]
    #[TestWith([9])]
    #[TestWith([0])]
    #[TestWith([-10])]
    public function testInvalidRefreshInterval(int $seconds): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The refresh interval of a widget must be between 10 and 86400 seconds (%d given).', $seconds));

        DummyWidget::new()->setRefreshInterval($seconds);
    }

    public function testMinimumCacheTime(): void
    {
        $this->assertSame(1, DummyWidget::new()->setCache(1)->getAsDto()->getCacheTtl());
    }

    #[TestWith([0])]
    #[TestWith([-1])]
    public function testInvalidCacheTime(int $seconds): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The number of seconds that a widget is cached must be 1 or more (%d given).', $seconds));

        DummyWidget::new()->setCache($seconds);
    }

    #[TestWith(['users'])]
    #[TestWith(['total_users'])]
    #[TestWith(['total-users-2'])]
    #[TestWith(['A'])]
    public function testValidNames(string $name): void
    {
        $this->assertSame($name, DummyWidget::new()->setName($name)->getAsDto()->getName());
    }

    #[TestWith([''])]
    #[TestWith(['total users'])]
    #[TestWith(['users/2'])]
    #[TestWith(['users.count'])]
    #[TestWith(['usuários'])]
    #[TestWith(["users\n"])]
    public function testInvalidNames(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name of a widget can only contain letters, numbers, dashes and underscores');

        DummyWidget::new()->setName($name);
    }

    #[TestWith([''])]
    #[TestWith(['   '])]
    public function testEmptyLinkUrl(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL of the widget link cannot be empty.');

        DummyWidget::new()->setLink($url);
    }

    public function testAddCssClassAccumulates(): void
    {
        $widget = DummyWidget::new()->addCssClass('foo')->addCssClass('bar baz');

        $this->assertSame('foo bar baz', $widget->getAsDto()->getCssClass());

        $widget->setCssClass(' qux ');
        $this->assertSame('qux', $widget->getAsDto()->getCssClass());

        $widget->addCssClass('quux');
        $this->assertSame('qux quux', $widget->getAsDto()->getCssClass());
    }

    public function testTemplateNameAndPathAreExclusive(): void
    {
        $widget = DummyWidget::new()->setTemplateName('widget/stat');

        $this->assertSame('widget/stat', $widget->getAsDto()->getTemplateName());
        $this->assertNull($widget->getAsDto()->getTemplatePath());

        $widget->setTemplatePath('admin/widget.html.twig');

        $this->assertNull($widget->getAsDto()->getTemplateName());
        $this->assertSame('admin/widget.html.twig', $widget->getAsDto()->getTemplatePath());

        $widget->setTemplateName('widget/list');

        $this->assertSame('widget/list', $widget->getAsDto()->getTemplateName());
        $this->assertNull($widget->getAsDto()->getTemplatePath());
    }

    public function testCssAndJsFilesAndHtmlContents(): void
    {
        $dto = DummyWidget::new()
            ->addCssFiles('css/widget.css', Asset::new('css/print.css'))
            ->addJsFiles('js/widget.js', Asset::new('js/chart.js')->defer())
            ->addHtmlContentsToHead('<meta name="a">', '<meta name="b">')
            ->addHtmlContentsToBody('<div id="c"></div>')
            ->getAsDto();

        $assets = $dto->getAssets();
        $this->assertSame(['css/widget.css', 'css/print.css'], array_keys($assets->getCssAssets()));
        $this->assertSame(['js/widget.js', 'js/chart.js'], array_keys($assets->getJsAssets()));
        $this->assertTrue($assets->getJsAssets()['js/chart.js']->isDefer());
        $this->assertSame(['<meta name="a">', '<meta name="b">'], $assets->getHeadContents());
        $this->assertSame(['<div id="c"></div>'], $assets->getBodyContents());
    }

    public function testAssetMapperEntries(): void
    {
        if (!class_exists('Symfony\\Component\\AssetMapper\\AssetMapper')) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('composer require symfony/asset-mapper');
        }

        $dto = DummyWidget::new()->addAssetMapperEntries('widgets')->getAsDto();

        $this->assertSame(['widgets'], array_keys($dto->getAssets()->getAssetMapperAssets()));
    }

    public function testWebpackEncoreEntries(): void
    {
        if (!class_exists('Symfony\\WebpackEncoreBundle\\Twig\\EntryFilesTwigExtension')) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('composer require symfony/webpack-encore-bundle');
        }

        $dto = DummyWidget::new()->addWebpackEncoreEntries('widgets')->getAsDto();

        $this->assertSame(['widgets'], array_keys($dto->getAssets()->getWebpackEncoreAssets()));
    }

    public function testRepriseEntries(): void
    {
        if (!class_exists('Symfony\\Reprise\\Twig\\AssetExtension')) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('composer require symfony/reprise');
        }

        $dto = DummyWidget::new()->addRepriseEntries('widgets')->getAsDto();

        $this->assertSame(['widgets'], array_keys($dto->getAssets()->getRepriseAssets()));
    }

    public function testRenderWithoutCard(): void
    {
        $widget = DummyWidget::new()->renderWithoutCard();
        $this->assertTrue($widget->getAsDto()->isRenderedWithoutCard());

        $widget->renderWithoutCard(false);
        $this->assertFalse($widget->getAsDto()->isRenderedWithoutCard());
    }

    public function testSetPlacement(): void
    {
        $widget = DummyWidget::new()->setPlacement(WidgetPlacement::Toolbar);

        $this->assertSame(WidgetPlacement::Toolbar, $widget->getAsDto()->getPlacement());
    }

    public function testCustomOptions(): void
    {
        $widget = DummyWidget::new()->setCustomOption('color', 'red');
        $this->assertSame('red', $widget->getAsDto()->getCustomOption('color'));
    }

    public function testCloneDeepCopiesTheDto(): void
    {
        $original = DummyWidget::new('Users')
            ->addCssFiles('css/original.css')
            ->setCustomOption('color', 'red')
            ->setValue('total', 10);

        $clone = clone $original;
        $clone->setLabel('Clone')
            ->addCssFiles('css/clone.css')
            ->setCustomOption('color', 'blue')
            ->setValue('total', 20);

        $this->assertNotSame($original->getAsDto(), $clone->getAsDto());
        $this->assertNotSame($original->getAsDto()->getAssets(), $clone->getAsDto()->getAssets());
        $this->assertNotSame($original->getAsDto()->getCustomOptions(), $clone->getAsDto()->getCustomOptions());

        $this->assertSame('Users', $original->getAsDto()->getLabel());
        $this->assertSame(['css/original.css'], array_keys($original->getAsDto()->getAssets()->getCssAssets()));
        $this->assertSame('red', $original->getAsDto()->getCustomOption('color'));
        $this->assertSame(['total' => 10], $original->getAsDto()->getData());

        $this->assertSame('Clone', $clone->getAsDto()->getLabel());
        $this->assertSame(['css/original.css', 'css/clone.css'], array_keys($clone->getAsDto()->getAssets()->getCssAssets()));
        $this->assertSame('blue', $clone->getAsDto()->getCustomOption('color'));
        $this->assertSame(['total' => 20], $clone->getAsDto()->getData());
    }
}
