<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EntityRepositoryTest extends TestCase
{
    private AdminContextProviderInterface $adminContextProvider;
    private ManagerRegistry $doctrine;
    private EventDispatcherInterface $eventDispatcher;
    private EntityRepository $entityRepository;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // use reflection to create EntityRepository without needing to mock final classes
        // EntityFactory and FormFactory are only used in specific scenarios
        $entityFactory = $this->createEntityFactoryStub();
        $formFactory = $this->createFormFactoryStub();

        $this->entityRepository = new EntityRepository(
            $this->adminContextProvider,
            $this->doctrine,
            $entityFactory,
            $formFactory,
            $this->eventDispatcher
        );
    }

    public function testCreateQueryBuilderReturnsQueryBuilder(): void
    {
        $searchDto = $this->createSearchDto();
        $entityDto = $this->createEntityDto();
        $fields = FieldCollection::new([]);
        $filters = FilterCollection::new();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->doctrine
            ->method('getManagerForClass')
            ->with('App\Entity\Product')
            ->willReturn($entityManager);

        $result = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $this->assertSame($queryBuilder, $result);
    }

    public function testCreateQueryBuilderWithEmptyQueryDoesNotAddSearchClause(): void
    {
        $searchDto = $this->createSearchDto();
        $entityDto = $this->createEntityDto();
        $fields = FieldCollection::new([]);
        $filters = FilterCollection::new();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        // no andWhere or orWhere for search should be called
        $queryBuilder->expects($this->never())->method('andWhere');
        $queryBuilder->expects($this->never())->method('orWhere');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->doctrine
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    public function testCreateQueryBuilderWithSortAddsOrderClause(): void
    {
        $searchDto = $this->createSearchDto('', ['name' => 'ASC']);
        $entityDto = $this->createEntityDto();
        $fields = FieldCollection::new([]);
        $filters = FilterCollection::new();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('getAllAliases')->willReturn(['entity']);
        $queryBuilder
            ->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.name', 'ASC');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->doctrine
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    public function testCreateQueryBuilderWithMultipleSortFields(): void
    {
        $searchDto = $this->createSearchDto('', ['name' => 'ASC', 'createdAt' => 'DESC']);
        $entityDto = $this->createEntityDto();
        $fields = FieldCollection::new([]);
        $filters = FilterCollection::new();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('getAllAliases')->willReturn(['entity']);
        $queryBuilder
            ->expects($this->exactly(2))
            ->method('addOrderBy')
            ->willReturnSelf();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->doctrine
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    public function testCreateQueryBuilderWithNoAppliedFiltersDoesNotCallFormFactory(): void
    {
        $searchDto = $this->createSearchDto('', [], null);
        $entityDto = $this->createEntityDto();
        $fields = FieldCollection::new([]);
        $filters = FilterCollection::new();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('getAllAliases')->willReturn(['entity']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->doctrine
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        // test completes without errors since filters are null
        $result = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $this->assertSame($queryBuilder, $result);
    }

    public function testCreateQueryBuilderWithEmptyAppliedFiltersDoesNotCallFormFactory(): void
    {
        $searchDto = $this->createSearchDto();
        $entityDto = $this->createEntityDto();
        $fields = FieldCollection::new([]);
        $filters = FilterCollection::new();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('getAllAliases')->willReturn(['entity']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->doctrine
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $result = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $this->assertSame($queryBuilder, $result);
    }

    public function testCreateQueryBuilderWithSearchQueryAttemptsToGetConnection(): void
    {
        $searchDto = $this->createSearchDto('test search');
        $entityDto = $this->createEntityDto();
        $fields = FieldCollection::new([]);
        $filters = FilterCollection::new();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('getAllAliases')->willReturn(['entity']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);
        $entityManager
            ->expects($this->once())
            ->method('getConnection')
            ->willThrowException(new \RuntimeException('Connection not available'));

        $this->doctrine
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        // when search query is not empty, it tries to get the connection
        // to determine the database platform for the search clause
        $result = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $this->assertSame($queryBuilder, $result);
    }

    private function createSearchDto(string $query = '', array $sort = [], ?array $appliedFilters = []): SearchDto
    {
        return new SearchDto(
            new Request(),
            null, // searchableProperties
            $query,
            $sort, // defaultSort
            [], // customSort
            $appliedFilters
        );
    }

    private function createEntityDto(): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getFieldNames')->willReturn([]);
        $metadata->fieldMappings = [];

        return new EntityDto('App\Entity\Product', $metadata);
    }

    /**
     * Creates a stub for EntityFactory using reflection since it's a final class.
     */
    private function createEntityFactoryStub(): EntityFactory
    {
        return (new \ReflectionClass(EntityFactory::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * Creates a stub for FormFactory using reflection since it's a final class.
     */
    private function createFormFactoryStub(): FormFactory
    {
        return (new \ReflectionClass(FormFactory::class))
            ->newInstanceWithoutConstructor();
    }
}
