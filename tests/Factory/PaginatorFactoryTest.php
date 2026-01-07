<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PaginatorFactoryTest extends TestCase
{
    private AdminContextProviderInterface $adminContextProvider;
    private EntityPaginatorInterface $entityPaginator;
    private PaginatorFactory $paginatorFactory;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->entityPaginator = $this->createMock(EntityPaginatorInterface::class);
        $this->paginatorFactory = new PaginatorFactory($this->adminContextProvider, $this->entityPaginator);
    }

    public function testCreateReturnsPaginatedResults(): void
    {
        $paginatorDto = new PaginatorDto(10, 3, 1, true, null);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $crudDto = new CrudDto();
        $crudDto->setPaginator($paginatorDto);

        $request = new Request(['page' => '5']);
        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($request),
            CrudContext::forTesting($crudDto)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        $capturedDto = null;
        $this->entityPaginator
            ->expects($this->once())
            ->method('paginate')
            ->willReturnCallback(function (PaginatorDto $dto, $qb) use (&$capturedDto) {
                $capturedDto = $dto;

                return $this->entityPaginator;
            });

        $result = $this->paginatorFactory->create($queryBuilder);

        $this->assertSame($this->entityPaginator, $result);
        $this->assertSame(5, $capturedDto->getPageNumber());
        $this->assertSame(10, $capturedDto->getPageSize());
        $this->assertSame(3, $capturedDto->getRangeSize());
    }

    public function testCreateDefaultsToPage1WhenNoPageParameter(): void
    {
        $paginatorDto = new PaginatorDto(20, 5, 2, false, true);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $crudDto = new CrudDto();
        $crudDto->setPaginator($paginatorDto);

        $request = new Request(); // no page parameter
        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($request),
            CrudContext::forTesting($crudDto)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        $capturedDto = null;
        $this->entityPaginator
            ->expects($this->once())
            ->method('paginate')
            ->willReturnCallback(function (PaginatorDto $dto, $qb) use (&$capturedDto) {
                $capturedDto = $dto;

                return $this->entityPaginator;
            });

        $result = $this->paginatorFactory->create($queryBuilder);

        $this->assertSame($this->entityPaginator, $result);
        $this->assertSame(1, $capturedDto->getPageNumber());
    }

    public function testCreateConvertsStringPageToInteger(): void
    {
        $paginatorDto = new PaginatorDto(15, 4, 1, true, null);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $crudDto = new CrudDto();
        $crudDto->setPaginator($paginatorDto);

        $request = new Request(['page' => '42']);
        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($request),
            CrudContext::forTesting($crudDto)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        $capturedDto = null;
        $this->entityPaginator
            ->expects($this->once())
            ->method('paginate')
            ->willReturnCallback(function (PaginatorDto $dto, $qb) use (&$capturedDto) {
                $capturedDto = $dto;

                return $this->entityPaginator;
            });

        $this->paginatorFactory->create($queryBuilder);

        $this->assertSame(42, $capturedDto->getPageNumber());
    }

    public function testCreateHandlesInvalidPageParameterAsPage1(): void
    {
        $paginatorDto = new PaginatorDto(10, 3, 1, true, null);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $crudDto = new CrudDto();
        $crudDto->setPaginator($paginatorDto);

        // invalid page parameter (non-numeric string)
        $request = new Request(['page' => 'invalid']);
        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($request),
            CrudContext::forTesting($crudDto)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        $capturedDto = null;
        $this->entityPaginator
            ->expects($this->once())
            ->method('paginate')
            ->willReturnCallback(function (PaginatorDto $dto, $qb) use (&$capturedDto) {
                $capturedDto = $dto;

                return $this->entityPaginator;
            });

        $this->paginatorFactory->create($queryBuilder);

        // (int)'invalid' = 0
        $this->assertSame(0, $capturedDto->getPageNumber());
    }

    public function testCreatePreservesPaginatorDtoSettings(): void
    {
        $paginatorDto = new PaginatorDto(25, 7, 3, false, false);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $crudDto = new CrudDto();
        $crudDto->setPaginator($paginatorDto);

        $request = new Request(['page' => '3']);
        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($request),
            CrudContext::forTesting($crudDto)
        );

        $this->adminContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->willReturn($adminContext);

        $capturedDto = null;
        $this->entityPaginator
            ->expects($this->once())
            ->method('paginate')
            ->willReturnCallback(function (PaginatorDto $dto, $qb) use (&$capturedDto) {
                $capturedDto = $dto;

                return $this->entityPaginator;
            });

        $this->paginatorFactory->create($queryBuilder);

        $this->assertSame(25, $capturedDto->getPageSize());
        $this->assertSame(7, $capturedDto->getRangeSize());
        $this->assertSame(3, $capturedDto->getRangeEdgeSize());
        $this->assertFalse($capturedDto->fetchJoinCollection());
        $this->assertFalse($capturedDto->useOutputWalkers());
    }
}
