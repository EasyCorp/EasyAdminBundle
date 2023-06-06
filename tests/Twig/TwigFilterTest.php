<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFilter;

final class TwigFilterTest extends KernelTestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->twig = $this->getContainer()->get(Environment::class);
    }

    public function myFilter($number, int $decimals, string $decPoint, string $thousandsSep)
    {
        return number_format($number, $decimals, $decPoint, $thousandsSep);
    }

    public function testNonLazyLoadedFilter(): void
    {
        $this->twig->addFilter(new TwigFilter('my_filter', [$this, 'myFilter']));

        $view = "{{number | ea_apply_filter_if_exists('my_filter', 2, ',', '.')}}";
        $context = ['number' => 123_456.789];
        $result = $this->twig->createTemplate($view)->render($context);
        $this->assertSame('123.456,79', $result);
    }

    public function testLazyLoadedFilter(): void
    {
        $loader = new FactoryRuntimeLoader([
            'EATests\MyLazyFilterRuntime' => fn () => new class() {
                public function myFilter($number, int $decimals, string $decPoint, string $thousandsSep)
                {
                    return number_format($number, $decimals, $decPoint, $thousandsSep);
                }
            },
        ]);

        $this->twig->addRuntimeLoader($loader);
        $this->twig->addFilter(new TwigFilter('my_filter', ['EATests\MyLazyFilterRuntime', 'myFilter']));

        $view = "{{number | ea_apply_filter_if_exists('my_filter', 2, ',', '.')}}";
        $context = ['number' => 123_456.789];
        $result = $this->twig->createTemplate($view)->render($context);
        $this->assertSame('123.456,79', $result);
    }

    public function testBuiltinFilter(): void
    {
        $view = "{{number | ea_apply_filter_if_exists('abs')}}";
        $context = ['number' => -10];
        $result = $this->twig->createTemplate($view)->render($context);
        $this->assertSame('10', $result);
    }

    public function testNotFoundFilter(): void
    {
        $view = "{{number | ea_apply_filter_if_exists('imagine_filter')}}";
        $context = ['number' => 3.14];
        $result = $this->twig->createTemplate($view)->render($context);
        $this->assertSame('3.14', $result);
    }

    public function testNotFoundClass(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('a');

        $this->twig->addFilter(new TwigFilter('my_filter', ['EATests\NotFoundClass', 'myFilter']));

        $view = "{{number | ea_apply_filter_if_exists('my_filter')}}";
        $context = ['number' => 123_456.789];
        $this->twig->createTemplate($view)->render($context);
    }

    public function testInvalidCallableString(): void
    {
        $this->twig->addFilter(new TwigFilter('my_filter', 'not-callable'));

        $view = "{{number | ea_apply_filter_if_exists('my_filter')}}";
        $context = ['number' => 123_456.789];
        $result = $this->twig->createTemplate($view)->render($context);
        $this->assertSame('', $result);
    }

    public function testInvalidCallableArray(): void
    {
        $loader = new FactoryRuntimeLoader([
            'EATests\MyLazyFilterRuntime' => fn () => new class() {},
        ]);

        $this->twig->addRuntimeLoader($loader);
        $this->twig->addFilter(new TwigFilter('my_filter', ['EATests\MyLazyFilterRuntime', 'unknownMethod']));

        $view = "{{number | ea_apply_filter_if_exists('my_filter')}}";
        $context = ['number' => 123_456.789];
        $result = $this->twig->createTemplate($view)->render($context);
        $this->assertSame('', $result);
    }
}
