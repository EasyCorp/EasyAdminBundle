<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\ArgumentResolver;

use EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver\BatchActionDtoResolver;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BatchActionDtoResolverTest extends TestCase
{
    private AdminContextProviderInterface $adminContextProvider;
    private BatchActionDtoResolver $resolver;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->resolver = new BatchActionDtoResolver($this->adminContextProvider);
    }

    public function testResolveReturnsEmptyArrayWhenArgumentTypeIsNotBatchActionDto(): void
    {
        $request = new Request();
        $argument = new ArgumentMetadata('dto', \stdClass::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertSame([], $result);
    }

    public function testResolveReturnsEmptyArrayWhenArgumentTypeIsNull(): void
    {
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

        // request uses pretty URLs (has CRUD_CONTROLLER_FQCN attribute)
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

        // request does not use pretty URLs (no CRUD_CONTROLLER_FQCN attribute)
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
     * @param array<string, mixed>     $payloadChanges    the fields to replace in a valid payload (null removes the field)
     * @param class-string<\Throwable> $expectedException both exceptions end up as a 400 response
     */
    #[DataProvider('provideInvalidPayloads')]
    public function testResolveRejectsInvalidPayloads(array $payloadChanges, string $expectedException): void
    {
        $payload = array_filter(array_merge([
            EA::BATCH_ACTION_NAME => 'delete',
            EA::BATCH_ACTION_ENTITY_IDS => ['1', '2'],
            EA::ENTITY_FQCN => 'App\Entity\Product',
            EA::BATCH_ACTION_CSRF_TOKEN => 'test_csrf_token',
        ], $payloadChanges), static fn (mixed $value): bool => null !== $value);

        $this->adminContextProvider
            ->method('getContext')
            ->willReturn(AdminContext::forTesting(RequestContext::forTesting(new Request(request: $payload))));

        $argument = new ArgumentMetadata('dto', BatchActionDto::class, false, false, null);

        $this->expectException($expectedException);

        iterator_to_array($this->resolver->resolve(new Request(), $argument));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string<\Throwable>}>
     */
    public static function provideInvalidPayloads(): iterable
    {
        yield 'missing name' => [[EA::BATCH_ACTION_NAME => null], BadRequestHttpException::class];
        yield 'missing entity FQCN' => [[EA::ENTITY_FQCN => null], BadRequestHttpException::class];
        yield 'missing CSRF token' => [[EA::BATCH_ACTION_CSRF_TOKEN => null], BadRequestHttpException::class];
        yield 'empty name' => [[EA::BATCH_ACTION_NAME => ''], BadRequestHttpException::class];
        yield 'empty entity FQCN' => [[EA::ENTITY_FQCN => ''], BadRequestHttpException::class];
        yield 'empty CSRF token' => [[EA::BATCH_ACTION_CSRF_TOKEN => ''], BadRequestHttpException::class];
        yield 'name sent as an array' => [[EA::BATCH_ACTION_NAME => ['delete']], BadRequestException::class];
        yield 'entity FQCN sent as an array' => [[EA::ENTITY_FQCN => ['App\Entity\Product']], BadRequestException::class];
        yield 'CSRF token sent as an array' => [[EA::BATCH_ACTION_CSRF_TOKEN => ['test_csrf_token']], BadRequestException::class];
        yield 'entity IDs sent as a scalar' => [[EA::BATCH_ACTION_ENTITY_IDS => '5'], BadRequestException::class];
        yield 'nested entity IDs' => [[EA::BATCH_ACTION_ENTITY_IDS => ['1', ['2']]], BadRequestHttpException::class];
    }
}
