<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetSectionDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetSection;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Translation\TranslatableMessage;

class WidgetSectionTest extends TestCase
{
    public function testDefaults(): void
    {
        $dto = WidgetSection::new()->getAsDto();

        $this->assertNull($dto->getName());
        $this->assertNull($dto->getLabel());
        $this->assertNull($dto->getIcon());
        $this->assertNull($dto->getHelp());
        $this->assertNull($dto->getPermission());
        $this->assertSame('', $dto->getCssClass());
        $this->assertSame([], $dto->getWidgets());
    }

    public function testSetters(): void
    {
        $label = new TranslatableMessage('section.sales');
        $permission = new Expression('is_granted("ROLE_SALES")');

        $dto = WidgetSection::new($label, 'fa fa-chart-line')
            ->setName('sales')
            ->setHelp('Sales of this month')
            ->setPermission($permission)
            ->addCssClass('foo')
            ->addCssClass('bar')
            ->getAsDto();

        $this->assertSame('sales', $dto->getName());
        $this->assertSame($label, $dto->getLabel());
        $this->assertSame('fa fa-chart-line', $dto->getIcon());
        $this->assertSame('Sales of this month', $dto->getHelp());
        $this->assertSame($permission, $dto->getPermission());
        $this->assertSame('foo bar', $dto->getCssClass());
    }

    public function testSetCssClassReplacesTheClasses(): void
    {
        $dto = WidgetSection::new()->addCssClass('foo')->setCssClass(' bar ')->getAsDto();

        $this->assertSame('bar', $dto->getCssClass());
    }

    #[TestWith([''])]
    #[TestWith(['sales section'])]
    #[TestWith(['sales.2025'])]
    #[TestWith(["sales\n"])]
    public function testInvalidNames(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name of a widget section can only contain letters, numbers, dashes and underscores');

        WidgetSection::new()->setName($name);
    }

    public function testCloneCopiesTheDto(): void
    {
        $original = WidgetSection::new('Sales');
        $clone = (clone $original)->setName('clone');

        $this->assertNotSame($original->getAsDto(), $clone->getAsDto());
        $this->assertNull($original->getAsDto()->getName());
        $this->assertSame('clone', $clone->getAsDto()->getName());
    }

    public function testDtoWidgets(): void
    {
        $first = new WidgetDto();
        $second = new WidgetDto();

        $dto = new WidgetSectionDto();
        $dto->addWidget($first);
        $dto->addWidget($second);

        $this->assertSame([$first, $second], $dto->getWidgets());
    }
}
