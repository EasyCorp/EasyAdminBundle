<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use function Symfony\Component\Translation\t;

class ActionTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testDeprecatedActionLabels(): void
    {
        $this->expectDeprecation('Since easycorp/easyadmin-bundle 4.0.5: Argument "$label" for "EasyCorp\Bundle\EasyAdminBundle\Config\Action::new" must be one of these types: "Symfony\Contracts\Translation\TranslatableInterface", "string", "callable", "false" or "null". Passing type "integer" will cause an error in 5.0.0.');

        Action::new(Action::EDIT, 7);
    }

    /**
     * @dataProvider provideAutomaticActionLabels
     */
    public function testActionWithAutomaticLabel(string $actionName, string $automaticLabel): void
    {
        $actionConfig = Action::new($actionName)->linkToCrudAction('');

        $this->assertSame($automaticLabel, $actionConfig->getAsDto()->getLabel());
    }

    /**
     * @dataProvider provideActionLabels
     */
    public function testAllPossibleValuesForActionLabels($label): void
    {
        $actionConfig = Action::new(Action::EDIT, $label)->linkToCrudAction('');

        $this->assertSame($label, $actionConfig->getAsDto()->getLabel());
    }

    public function testCallableLabelForDynamicLabelGeneration(): void
    {
        $callable = static function (object $entity) {
            return sprintf('Delete %s', $entity);
        };

        $actionConfig = Action::new(Action::DELETE)
            ->setLabel($callable)
            ->linkToCrudAction('');

        $dto = $actionConfig->getAsDto();

        $this->assertSame($callable, $dto->getLabel());
    }

    public function testDefaultCssClass(): void
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('');

        $this->assertSame('', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testSetCssClass(): void
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->setCssClass('foo');

        $this->assertSame('foo', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testAddCssClass(): void
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->addCssClass('foo');

        $this->assertSame('', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('foo', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testSetAndAddCssClass(): void
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->setCssClass('foo')->addCssClass('bar');

        $this->assertSame('foo', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('bar', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testSetAndAddCssClassWithSpaces(): void
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->setCssClass('      foo1   foo2  ')->addCssClass('     bar1    bar2   ');

        $this->assertSame('foo1   foo2', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('bar1    bar2', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public static function provideAutomaticActionLabels(): iterable
    {
        // format: (action name, automatic label generated for the action)
        yield ['Edit', 'Edit'];
        yield ['FooBar', 'Foo Bar'];
        yield ['fooBar', 'Foo Bar'];
        yield ['foo_Bar', 'Foo Bar'];
    }

    public static function provideActionLabels(): iterable
    {
        yield [false];
        yield [''];
        yield ['Edit'];
        yield [fn (object $entity) => sprintf('Edit %s', $entity)];
        yield [static function (object $entity) {
            return sprintf('Edit %s', $entity);
        }];
        yield [t('Edit')];
    }

    public function testGetAsConfigObjectPreservesLinkToUrl(): void
    {
        $action = Action::new('my_action')
            ->linkToUrl('https://example.com');

        $dto = $action->getAsDto();
        $restoredAction = $dto->getAsConfigObject();
        $restoredDto = $restoredAction->getAsDto();

        $this->assertSame('https://example.com', $restoredDto->getUrl());
    }

    public function testGetAsConfigObjectPreservesCallableUrl(): void
    {
        $callable = fn () => 'https://dynamic.example.com';
        $action = Action::new('my_action')
            ->linkToUrl($callable);

        $dto = $action->getAsDto();
        $restoredAction = $dto->getAsConfigObject();
        $restoredDto = $restoredAction->getAsDto();

        $this->assertSame($callable, $restoredDto->getUrl());
    }

    public function testGetAsConfigObjectPreservesLinkToRoute(): void
    {
        $action = Action::new('my_action')
            ->linkToRoute('my_route', ['param1' => 'value1']);

        $dto = $action->getAsDto();
        $restoredAction = $dto->getAsConfigObject();
        $restoredDto = $restoredAction->getAsDto();

        $this->assertSame('my_route', $restoredDto->getRouteName());
        $this->assertSame(['param1' => 'value1'], $restoredDto->getRouteParameters());
    }

    public function testGetAsConfigObjectPreservesLinkToCrudAction(): void
    {
        $action = Action::new('my_action')
            ->linkToCrudAction('edit');

        $dto = $action->getAsDto();
        $restoredAction = $dto->getAsConfigObject();
        $restoredDto = $restoredAction->getAsDto();

        $this->assertSame('edit', $restoredDto->getCrudActionName());
    }
}
