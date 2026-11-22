<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Dto\WidgetDto;
use EasyCorp\Bundle\EasyAdminBundle\Widget\TwigWidget;
use EasyCorp\Bundle\EasyAdminBundle\Widget\WidgetContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class TwigWidgetTest extends TestCase
{
    public function testDefaults(): void
    {
        $dto = TwigWidget::new()->getAsDto();

        self::assertNull($dto->getLabel());
        self::assertSame('widget/twig', $dto->getTemplateName());
        self::assertSame(12, $dto->getColumns());
        self::assertSame('twig', $dto->getType());
        self::assertNull($dto->getCustomOption(TwigWidget::OPTION_TEMPLATE_PATH));
        self::assertSame([], $dto->getCustomOption(TwigWidget::OPTION_TEMPLATE_VARS));
    }

    public function testLabel(): void
    {
        self::assertSame('Welcome', TwigWidget::new('Welcome')->getAsDto()->getLabel());
    }

    public function testSetTemplate(): void
    {
        $dto = TwigWidget::new()->setTemplate('admin/widget/welcome.html.twig', ['name' => 'Name 1'])->getAsDto();

        self::assertSame('widget/twig', $dto->getTemplateName());
        self::assertNull($dto->getTemplatePath());
        self::assertSame('admin/widget/welcome.html.twig', $dto->getCustomOption(TwigWidget::OPTION_TEMPLATE_PATH));
        self::assertSame(['name' => 'Name 1'], $dto->getCustomOption(TwigWidget::OPTION_TEMPLATE_VARS));
        self::assertSame(['template' => 'admin/widget/welcome.html.twig', 'vars' => ['name' => 'Name 1']], $this->resolveData($dto));
    }

    public function testTemplateVariablesAreOptional(): void
    {
        $dto = TwigWidget::new()->setTemplate('admin/widget/welcome.html.twig')->getAsDto();

        self::assertSame([], $this->resolveData($dto)['vars']);
    }

    public function testClosureVariablesReceiveTheContext(): void
    {
        $dto = TwigWidget::new()
            ->setTemplate('admin/widget/welcome.html.twig', static fn (WidgetContext $context): array => ['range' => $context->getAttribute('range')])
            ->getAsDto();

        self::assertSame(['range' => '30d'], $this->resolveData($dto, ['range' => '30d'])['vars']);
    }

    public function testArrayVariablesThatLookLikeCallablesAreNeverCalled(): void
    {
        $vars = [new \ArrayObject(), 'count'];
        $dto = TwigWidget::new()->setTemplate('admin/widget/welcome.html.twig', $vars)->getAsDto();

        self::assertSame($vars, $this->resolveData($dto)['vars']);
    }

    /**
     * @return iterable<string, array{callable}>
     */
    public static function provideStringCallables(): iterable
    {
        yield 'function name' => ['time'];
        yield 'dangerous function name' => ['phpinfo'];
    }

    #[DataProvider('provideStringCallables')]
    public function testStringCallablesAreRejected(callable $callable): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts arrays, closures and invokable objects as template variables (string given)');

        TwigWidget::new()->setTemplate('admin/widget/welcome.html.twig', $callable);
    }

    public function testEmptyTemplatePathIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The template path of a Twig widget cannot be empty.');

        TwigWidget::new()->setTemplate(' ');
    }

    public function testWidgetWithoutTemplateFailsWhenRendered(): void
    {
        $dto = TwigWidget::new()->setName('welcome')->getAsDto();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "welcome" Twig widget doesn\'t define the template to render. Call its "setTemplate()" method.');

        $this->resolveData($dto);
    }

    public function testClosureMustReturnAnArray(): void
    {
        $dto = TwigWidget::new()->setName('welcome')->setTemplate('admin/widget/welcome.html.twig', static fn (): string => 'Name 1')->getAsDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The template variables of the "welcome" Twig widget must be an array (string given).');

        $this->resolveData($dto);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    private function resolveData(WidgetDto $dto, array $attributes = []): array
    {
        $context = new WidgetContext(new Request(), null, 'App\Controller\Admin\DashboardController', $dto, $attributes);

        return array_map(static fn (mixed $valueOrCallable): mixed => $context->resolve($valueOrCallable), $dto->getData());
    }
}
