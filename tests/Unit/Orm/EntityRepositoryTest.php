<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
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
        // entityFactory and FormFactory are only used in specific scenarios
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
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

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
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

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
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

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
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

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
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

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
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

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
        $fields = new FieldCollection([]);
        $filters = new FilterCollection();

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

    public function testCustomSortByExposedSortableFieldIsApplied(): void
    {
        $entityDto = $this->createEntityDto(['displayedField']);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['displayedField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.displayedField', 'ASC');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortByFieldAbsentFromFieldCollectionIsIgnored(): void
    {
        // simulates ?sort[hiddenField]=ASC against a controller whose
        // configureFields(INDEX) doesn't expose `hiddenField`
        $entityDto = $this->createEntityDto(['hiddenField']);
        $fields = new FieldCollection([]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['hiddenField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortByExplicitlyNonSortableFieldIsIgnored(): void
    {
        $entityDto = $this->createEntityDto(['displayedField']);
        $fields = new FieldCollection([$this->createField('displayedField', false)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['displayedField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortKeyContainingCommaIsIgnored(): void
    {
        // ?sort[name,entity.email]=ASC — comma would smuggle an extra ORDER BY column
        $entityDto = $this->createEntityDto(['displayedField']);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['displayedField,entity.hiddenField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortKeyContainingDotIsIgnored(): void
    {
        // ?sort[customer.secretField]=ASC — multi-segment keys reach the unfiltered
        // multi-segment branch of applyOrderClause; URL-based association sort is
        // supported via single-segment keys + AssociationField::setSortProperty()
        $entityDto = $this->createEntityDto([], ['customer']);
        $fields = new FieldCollection([$this->createField('customer', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer.secretField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');
        $queryBuilder->expects($this->never())->method('leftJoin');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortWithNonAscDescValueIsIgnored(): void
    {
        // ?sort[displayedField]=ASC,%20entity.hiddenField%20DESC — Expr\OrderBy
        // concatenates "$property $direction", so an unvalidated direction smuggles
        // a second OrderByItem that the DQL parser happily accepts
        $entityDto = $this->createEntityDto(['displayedField']);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['displayedField' => 'ASC, entity.hiddenField DESC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testInvalidCustomSortFallsBackToDefaultSortForSameKey(): void
    {
        // ?sort[hiddenField]=ASC must not suppress setDefaultSort(['hiddenField' => 'DESC']):
        // the customSort entry is rejected, the defaultSort entry still applies
        $entityDto = $this->createEntityDto(['hiddenField']);
        $fields = new FieldCollection([]);
        $searchDto = $this->createSearchDtoForSort(
            customSort: ['hiddenField' => 'ASC'],
            defaultSort: ['hiddenField' => 'DESC'],
        );

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.hiddenField', 'DESC');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testDefaultSortByFieldAbsentFromFieldCollectionIsStillApplied(): void
    {
        // developer-supplied default sort is trusted unconditionally
        $entityDto = $this->createEntityDto(['createdAt']);
        $fields = new FieldCollection([]);
        $searchDto = $this->createSearchDtoForSort(defaultSort: ['createdAt' => 'DESC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.createdAt', 'DESC');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testValidCustomSortOverridesDefaultSortForSameKey(): void
    {
        $entityDto = $this->createEntityDto(['displayedField']);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(
            customSort: ['displayedField' => 'ASC'],
            defaultSort: ['displayedField' => 'DESC'],
        );

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.displayedField', 'ASC');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
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

    /**
     * @param array<string, string> $customSort
     * @param array<string, string> $defaultSort
     */
    private function createSearchDtoForSort(array $customSort = [], array $defaultSort = []): SearchDto
    {
        return new SearchDto(new Request(), null, '', $defaultSort, $customSort, []);
    }

    /**
     * @param list<string> $mappedFields
     * @param list<string> $mappedAssociations
     */
    private function createEntityDto(array $mappedFields = [], array $mappedAssociations = []): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasField')->willReturnCallback(static fn (string $name): bool => \in_array($name, $mappedFields, true));
        $metadata->method('hasAssociation')->willReturnCallback(static fn (string $name): bool => \in_array($name, $mappedAssociations, true));
        $metadata->method('getFieldNames')->willReturn($mappedFields);
        $metadata->fieldMappings = array_fill_keys($mappedFields, []);

        return new EntityDto('App\Entity\Product', $metadata);
    }

    private function createField(string $property, bool $sortable): FieldInterface
    {
        $field = Field::new($property);
        $field->setSortable($sortable);

        return $field;
    }

    /**
     * Builds a QueryBuilder mock pre-wired for the select/from/getAllAliases
     * calls EntityRepository::createQueryBuilder makes before addOrderClause runs.
     */
    private function createSortingQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('getAllAliases')->willReturn(['entity']);

        return $queryBuilder;
    }

    private function stubEntityManager(QueryBuilder $queryBuilder): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->doctrine->method('getManagerForClass')->willReturn($entityManager);
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
