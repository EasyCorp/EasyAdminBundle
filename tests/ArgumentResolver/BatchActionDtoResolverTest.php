<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\BatchActionDtoResolver;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class BatchActionDtoResolverTest extends TestCase
{
    private AdminContextProviderInterface $adminContextProvider;
    private AdminUrlGeneratorInterface $adminUrlGenerator;
    private BatchActionDtoResolver $resolver;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->adminUrlGenerator = $this->createMock(AdminUrlGeneratorInterface::class);
        $this->resolver = new BatchActionDtoResolver($this->adminContextProvider, $this->adminUrlGenerator);
    }

    /**
     * @requires function Symfony\Component\HttpKernel\Controller\ValueResolverInterface::resolve
     */
    public function testResolveReturnsEmptyArrayWhenArgumentTypeIsNotBatchActionDto(): void
    {
        // This test only applies to Symfony 6+ where ValueResolverInterface is used
        // In Symfony 5.4, the supports() method handles type checking
        $request = new Request();
        $argument = new ArgumentMetadata('dto', \stdClass::class, false, false, null);

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
        $argument = new ArgumentMetadata('dto', null, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertSame([], $result);
    }

    public function testResolveThrowsExceptionWhenContextIsNull(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('dto', BatchActionDto::class, false, false, null);

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Some of your controller actions have type-hinted an argument with the "%s" class but that\'s only available for actions run to serve EasyAdmin requests.',
            BatchActionDto::class
        ));

        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function testResolveCreatesBatchActionDtoWithPrettyUrls(): void
    {
        $contextRequest = new Request(request: [
            EA::BATCH_ACTION_NAME => 'delete',
            EA::BATCH_ACTION_ENTITY_IDS => ['1', '2', '3'],
            EA::ENTITY_FQCN => 'App\Entity\Product',
            EA::BATCH_ACTION_CSRF_TOKEN => 'test_csrf_token',
        ]);

        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($contextRequest)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        // Request uses pretty URLs (has CRUD_CONTROLLER_FQCN attribute)
        $request = new Request();
        $request->attributes->set(EA::CRUD_CONTROLLER_FQCN, 'App\Controller\ProductCrudController');

        $argument = new ArgumentMetadata('dto', BatchActionDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(BatchActionDto::class, $result[0]);
        $this->assertSame('delete', $result[0]->getName());
        $this->assertSame(['1', '2', '3'], $result[0]->getEntityIds());
        $this->assertSame('App\Entity\Product', $result[0]->getEntityFqcn());
        $this->assertSame('test_csrf_token', $result[0]->getCsrfToken());
    }

    public function testResolveCreatesBatchActionDtoWithoutPrettyUrls(): void
    {
        $contextRequest = new Request(request: [
            EA::BATCH_ACTION_NAME => 'archive',
            EA::BATCH_ACTION_ENTITY_IDS => ['5', '10'],
            EA::ENTITY_FQCN => 'App\Entity\Order',
            EA::BATCH_ACTION_CSRF_TOKEN => 'another_token',
            EA::BATCH_ACTION_URL => '/admin?crudControllerFqcn=App%5CController%5COrderCrudController',
        ]);

        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($contextRequest)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        // Request does not use pretty URLs (no CRUD_CONTROLLER_FQCN attribute)
        $request = new Request([EA::CRUD_CONTROLLER_FQCN => 'App\Controller\OrderCrudController']);

        $argument = new ArgumentMetadata('dto', BatchActionDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(BatchActionDto::class, $result[0]);
        $this->assertSame('archive', $result[0]->getName());
        $this->assertSame(['5', '10'], $result[0]->getEntityIds());
        $this->assertSame('App\Entity\Order', $result[0]->getEntityFqcn());
        $this->assertSame('another_token', $result[0]->getCsrfToken());
    }

    public function testResolveHandlesEmptyEntityIds(): void
    {
        $contextRequest = new Request(request: [
            EA::BATCH_ACTION_NAME => 'export',
            EA::ENTITY_FQCN => 'App\Entity\User',
            EA::BATCH_ACTION_CSRF_TOKEN => 'csrf_123',
        ]);

        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($contextRequest)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        $request = new Request();
        $request->attributes->set(EA::CRUD_CONTROLLER_FQCN, 'App\Controller\UserCrudController');

        $argument = new ArgumentMetadata('dto', BatchActionDto::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertSame([], $result[0]->getEntityIds());
    }

    /**
     * Tests the supports() method for Symfony 5.4 compatibility.
     */
    public function testSupportsReturnsTrueWhenArgumentTypeIsBatchActionDto(): void
    {
        if (!method_exists($this->resolver, 'supports')) {
            $this->markTestSkipped('This test only applies to Symfony 5.4 (ArgumentValueResolverInterface)');
        }

        $request = new Request();
        $argument = new ArgumentMetadata('dto', BatchActionDto::class, false, false, null);

        $this->assertTrue($this->resolver->supports($request, $argument));
    }

    /**
     * Tests the supports() method for Symfony 5.4 compatibility.
     */
    public function testSupportsReturnsFalseWhenArgumentTypeIsNotBatchActionDto(): void
    {
        if (!method_exists($this->resolver, 'supports')) {
            $this->markTestSkipped('This test only applies to Symfony 5.4 (ArgumentValueResolverInterface)');
        }

        $request = new Request();
        $argument = new ArgumentMetadata('dto', \stdClass::class, false, false, null);

        $this->assertFalse($this->resolver->supports($request, $argument));
    }
}
