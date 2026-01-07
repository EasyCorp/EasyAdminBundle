<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\AdminContextResolver;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
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

    /**
     * @requires function Symfony\Component\HttpKernel\Controller\ValueResolverInterface::resolve
     */
    public function testResolveReturnsEmptyArrayWhenArgumentTypeIsNotAdminContext(): void
    {
        // This test only applies to Symfony 6+ where ValueResolverInterface is used
        // In Symfony 5.4, the supports() method handles type checking
        $request = new Request();
        $argument = new ArgumentMetadata('context', \stdClass::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertSame([], $result);
    }

    /**
     * @requires function Symfony\Component\HttpKernel\Controller\ValueResolverInterface::resolve
     */
    public function testResolveReturnsEmptyArrayWhenArgumentTypeIsNull(): void
    {
        // This test only applies to Symfony 6+ where ValueResolverInterface is used
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

    /**
     * Tests the supports() method for Symfony 5.4 compatibility.
     */
    public function testSupportsReturnsTrueWhenArgumentTypeIsAdminContext(): void
    {
        if (!method_exists($this->resolver, 'supports')) {
            $this->markTestSkipped('This test only applies to Symfony 5.4 (ArgumentValueResolverInterface)');
        }

        $request = new Request();
        $argument = new ArgumentMetadata('context', AdminContext::class, false, false, null);

        $this->assertTrue($this->resolver->supports($request, $argument));
    }

    /**
     * Tests the supports() method for Symfony 5.4 compatibility.
     */
    public function testSupportsReturnsFalseWhenArgumentTypeIsNotAdminContext(): void
    {
        if (!method_exists($this->resolver, 'supports')) {
            $this->markTestSkipped('This test only applies to Symfony 5.4 (ArgumentValueResolverInterface)');
        }

        $request = new Request();
        $argument = new ArgumentMetadata('context', \stdClass::class, false, false, null);

        $this->assertFalse($this->resolver->supports($request, $argument));
    }
}
