<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\AdminContextResolver;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AdminContextResolverTest extends TestCase
{
    private AdminContextProviderInterface $adminContextProvider;
    private AdminContextResolver $resolver;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->resolver = new AdminContextResolver($this->adminContextProvider);
    }

    public function testResolveReturnsEmptyArrayWhenArgumentTypeIsNotAdminContext(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('context', \stdClass::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertSame([], $result);
    }

    public function testResolveReturnsEmptyArrayWhenArgumentTypeIsNull(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('context', null, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertSame([], $result);
    }

    public function testResolveYieldsAdminContextWhenArgumentTypeMatches(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('context', AdminContext::class, false, false, null);
        $adminContext = $this->createMock(AdminContextInterface::class);

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertSame($adminContext, $result[0]);
    }

    public function testResolveYieldsNullContextWhenProviderReturnsNull(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('context', AdminContext::class, false, false, null);

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn(null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertNull($result[0]);
    }
}
