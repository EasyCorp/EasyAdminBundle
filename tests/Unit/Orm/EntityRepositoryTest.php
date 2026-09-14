<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory\EntityFactoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
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
    private EntityFactoryInterface $entityFactory;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityFactory = $this->createMock(EntityFactoryInterface::class);
        $formFactory = $this->createFormFactoryStub();

        $this->entityRepository = new EntityRepository(
            $this->adminContextProvider,
            $this->doctrine,
            $this->entityFactory,
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
            ->with('entity.name', self::expectedSortOrder('ASC'));

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
        $entityDto = $this->createEntityDto(['displayedField' => ['type' => 'string']]);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['displayedField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.displayedField', self::expectedSortOrder('ASC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    /**
     * @dataProvider provideCaseInsensitiveSortDirections
     */
    public function testCustomSortDirectionIsNormalizedRegardlessOfCase(string $requestedDirection, string $normalizedDirection): void
    {
        // the sort direction comes verbatim from the URL (?sort[displayedField]=desc)
        $entityDto = $this->createEntityDto(['displayedField' => ['type' => 'string']]);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['displayedField' => $requestedDirection]);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.displayedField', self::expectedSortOrder($normalizedDirection));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public static function provideCaseInsensitiveSortDirections(): iterable
    {
        yield 'lowercase desc' => ['desc', 'DESC'];
        yield 'lowercase asc' => ['asc', 'ASC'];
        yield 'mixed case desc' => ['dEsC', 'DESC'];
        yield 'mixed case asc' => ['Asc', 'ASC'];
    }

    public function testCustomSortByFieldAbsentFromFieldCollectionIsIgnored(): void
    {
        // simulates ?sort[hiddenField]=ASC against a controller whose
        // configureFields(INDEX) doesn't expose `hiddenField`
        $entityDto = $this->createEntityDto(['hiddenField' => ['type' => 'string']]);
        $fields = new FieldCollection([]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['hiddenField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortByExplicitlyNonSortableFieldIsIgnored(): void
    {
        $entityDto = $this->createEntityDto(['displayedField' => ['type' => 'string']]);
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
        $entityDto = $this->createEntityDto(['displayedField' => ['type' => 'string']]);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['displayedField,entity.hiddenField' => 'ASC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortByNestedPropertyNotExposedAsFieldIsIgnored(): void
    {
        // ?sort[customer.name]=ASC against a controller whose configureFields(INDEX)
        // doesn't expose the `customer.name` property: the path maps to real Doctrine
        // metadata, but only properties exposed as sortable fields can be sorted via the URL
        $customerEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $fields = new FieldCollection([$this->createField('customer', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer.name' => 'ASC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');
        $queryBuilder->expects($this->never())->method('leftJoin');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortByNestedPropertyNotMappedByDoctrineIsIgnored(): void
    {
        // ?sort[customer.secretField]=ASC — the last segment doesn't map to any
        // Doctrine field of the associated entity, so the whole key is rejected
        $customerEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $fields = new FieldCollection([$this->createField('customer.secretField', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer.secretField' => 'ASC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');
        $queryBuilder->expects($this->never())->method('leftJoin');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortByExposedSortableNestedPropertyIsApplied(): void
    {
        $customerEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $fields = new FieldCollection([$this->createField('customer.name', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer.name' => 'ASC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->method('getRootAliases')->willReturn(['entity']);
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('entity.customer', 'customer');
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('customer.name', self::expectedSortOrder('ASC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testDefaultSortByMultiLevelNestedPropertyAddsAllJoins(): void
    {
        $categoryEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\Category');
        $releaseEntityDto = $this->createEntityDto([], ['category' => 'App\Entity\Category'], 'App\Entity\Release');
        $entityDto = $this->createEntityDto([], ['latestRelease' => 'App\Entity\Release']);
        $searchDto = $this->createSearchDtoForSort(defaultSort: ['latestRelease.category.name' => 'DESC']);

        $this->entityFactory->method('create')->willReturnCallback(static fn (string $fqcn): EntityDto => match ($fqcn) {
            'App\Entity\Release' => $releaseEntityDto,
            'App\Entity\Category' => $categoryEntityDto,
        });

        $joins = [];
        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->method('getRootAliases')->willReturn(['entity']);
        $queryBuilder->method('leftJoin')->willReturnCallback(static function (string $join, string $alias) use (&$joins, $queryBuilder) {
            $joins[] = [$join, $alias];

            return $queryBuilder;
        });
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('category1.name', self::expectedSortOrder('DESC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, new FieldCollection([]), new FilterCollection());

        self::assertSame([['entity.latestRelease', 'latestRelease'], ['latestRelease.category', 'category1']], $joins);
    }

    public function testCustomSortOnAssociationCombinedWithNestedDefaultSortJoinsOnlyOnce(): void
    {
        // ?sort[customer]=ASC on a CRUD with setDefaultSort(['customer.name' => 'DESC']):
        // both sort properties traverse the 'customer' association, so its JOIN must be
        // added only once or Doctrine fails with "[Semantical Error] 'customer' is already defined"
        $customerEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $fields = new FieldCollection([$this->createField('customer', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer' => 'ASC'], defaultSort: ['customer.name' => 'DESC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

        $orderClauses = [];
        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->method('getRootAliases')->willReturn(['entity']);
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('entity.customer', 'customer')
            ->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnCallback(static function (string $sort, $order) use (&$orderClauses, $queryBuilder) {
            $orderClauses[] = [$sort, $order];

            return $queryBuilder;
        });

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());

        self::assertSame([['entity.customer', self::expectedSortOrder('ASC')], ['customer.name', self::expectedSortOrder('DESC')]], $orderClauses);
    }

    public function testDefaultSortByNestedAssociationLeafOrdersByItsForeignKey(): void
    {
        // setDefaultSort(['customer.country' => 'ASC']) where 'country' is itself an
        // association: with no setSortProperty(), the query orders by the leaf association
        // on its already-joined parent (i.e. its foreign key), mirroring how single-level
        // associations are ordered by 'entity.assoc'
        $customerEntityDto = $this->createEntityDto([], ['country' => 'App\Entity\Country'], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $searchDto = $this->createSearchDtoForSort(defaultSort: ['customer.country' => 'ASC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->method('getRootAliases')->willReturn(['entity']);
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('entity.customer', 'customer')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('customer.country', self::expectedSortOrder('ASC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, new FieldCollection([]), new FilterCollection());
    }

    public function testDefaultSortByNestedAssociationLeafWithSortPropertyOrdersByThatProperty(): void
    {
        // AssociationField::new('customer.country')->setSortProperty('name') sorts by
        // the 'name' property of the related Country entity: the leaf association is
        // also joined and the ORDER BY targets its resolved alias
        $countryEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\Country');
        $customerEntityDto = $this->createEntityDto([], ['country' => 'App\Entity\Country'], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $field = AssociationField::new('customer.country')->setSortProperty('name');
        $searchDto = $this->createSearchDtoForSort(defaultSort: ['customer.country' => 'ASC']);

        $this->entityFactory->method('create')->willReturnCallback(static fn (string $fqcn): EntityDto => match ($fqcn) {
            'App\Entity\Customer' => $customerEntityDto,
            'App\Entity\Country' => $countryEntityDto,
        });

        $joins = [];
        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->method('getRootAliases')->willReturn(['entity']);
        $queryBuilder->method('leftJoin')->willReturnCallback(static function (string $join, string $alias) use (&$joins, $queryBuilder) {
            $joins[] = [$join, $alias];

            return $queryBuilder;
        });
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('country1.name', self::expectedSortOrder('ASC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, new FieldCollection([$field]), new FilterCollection());

        self::assertSame([['entity.customer', 'customer'], ['customer.country', 'country1']], $joins);
    }

    public function testCustomSortByExposedSortableNestedAssociationLeafIsApplied(): void
    {
        // ?sort[customer.country]=DESC is valid when every segment is a real Doctrine
        // association, the leaf is single-valued and the property is exposed as a sortable field
        $customerEntityDto = $this->createEntityDto([], ['country' => 'App\Entity\Country'], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $fields = new FieldCollection([$this->createField('customer.country', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer.country' => 'DESC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->method('getRootAliases')->willReturn(['entity']);
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('entity.customer', 'customer')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('customer.country', self::expectedSortOrder('DESC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testCustomSortByNestedAssociationLeafNotExposedAsFieldIsIgnored(): void
    {
        // URL sort remains opt-in: the nested association path maps to real Doctrine
        // metadata, but the property is not exposed as a field of the INDEX page
        $customerEntityDto = $this->createEntityDto([], ['country' => 'App\Entity\Country'], 'App\Entity\Customer');
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer.country' => 'ASC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->never())->method('addOrderBy');
        $queryBuilder->expects($this->never())->method('leftJoin');

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, new FieldCollection([]), new FilterCollection());
    }

    public function testCustomSortByNestedToManyAssociationLeafIsIgnored(): void
    {
        // ?sort[customer.orders]=ASC where 'orders' is a to-many association: sorting by
        // a collection foreign key is undefined, so the key is rejected even though the
        // field is exposed as sortable
        $customerEntityDto = $this->createEntityDto([], ['orders' => 'App\Entity\Order'], 'App\Entity\Customer', ['orders']);
        $entityDto = $this->createEntityDto([], ['customer' => 'App\Entity\Customer']);
        $fields = new FieldCollection([$this->createField('customer.orders', true)]);
        $searchDto = $this->createSearchDtoForSort(customSort: ['customer.orders' => 'ASC']);

        $this->entityFactory->method('create')->willReturn($customerEntityDto);

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
        $entityDto = $this->createEntityDto(['displayedField' => ['type' => 'string']]);
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
        $entityDto = $this->createEntityDto(['hiddenField' => ['type' => 'string']]);
        $fields = new FieldCollection([]);
        $searchDto = $this->createSearchDtoForSort(
            customSort: ['hiddenField' => 'ASC'],
            defaultSort: ['hiddenField' => 'DESC'],
        );

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.hiddenField', self::expectedSortOrder('DESC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testDefaultSortByFieldAbsentFromFieldCollectionIsStillApplied(): void
    {
        // developer-supplied default sort is trusted unconditionally
        $entityDto = $this->createEntityDto(['createdAt' => ['type' => 'date_time']]);
        $fields = new FieldCollection([]);
        $searchDto = $this->createSearchDtoForSort(defaultSort: ['createdAt' => 'DESC']);

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.createdAt', self::expectedSortOrder('DESC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testValidCustomSortOverridesDefaultSortForSameKey(): void
    {
        $entityDto = $this->createEntityDto(['displayedField' => ['type' => 'string']]);
        $fields = new FieldCollection([$this->createField('displayedField', true)]);
        $searchDto = $this->createSearchDtoForSort(
            customSort: ['displayedField' => 'ASC'],
            defaultSort: ['displayedField' => 'DESC'],
        );

        $queryBuilder = $this->createSortingQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('entity.displayedField', self::expectedSortOrder('ASC'));

        $this->stubEntityManager($queryBuilder);

        $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, new FilterCollection());
    }

    public function testResolveNestedAssociationsWithSimpleProperty(): void
    {
        $rootEntityDto = $this->createEntityDto(['title' => ['type' => 'string']], [], 'App\Entity\Post');

        $resolved = $this->entityRepository->resolveNestedAssociations(null, $rootEntityDto, 'title');

        self::assertSame($rootEntityDto, $resolved->getEntityDto());
        self::assertNull($resolved->getEntityAlias());
        self::assertSame('title', $resolved->getPropertyName());
    }

    public function testResolveNestedAssociationsWithNestedProperty(): void
    {
        $authorEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\User');
        $rootEntityDto = $this->createEntityDto([], ['author' => 'App\Entity\User'], 'App\Entity\Post');

        $this->entityFactory->expects(self::once())
            ->method('create')
            ->with('App\Entity\User')
            ->willReturn($authorEntityDto);

        $queryBuilder = $this->createResolveQueryBuilder();
        $queryBuilder->expects(self::once())
            ->method('leftJoin')
            ->with('entity.author', 'author');

        $resolved = $this->entityRepository->resolveNestedAssociations($queryBuilder, $rootEntityDto, 'author.name');

        self::assertSame($authorEntityDto, $resolved->getEntityDto());
        self::assertSame('author', $resolved->getEntityAlias());
        self::assertSame('name', $resolved->getPropertyName());
    }

    public function testResolveNestedAssociationsWithMultiLevelNestedProperty(): void
    {
        $categoryEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\Category');
        $releaseEntityDto = $this->createEntityDto([], ['category' => 'App\Entity\Category'], 'App\Entity\Release');
        $rootEntityDto = $this->createEntityDto([], ['latestRelease' => 'App\Entity\Release'], 'App\Entity\Project');

        $this->entityFactory->method('create')->willReturnCallback(static fn (string $fqcn): EntityDto => match ($fqcn) {
            'App\Entity\Release' => $releaseEntityDto,
            'App\Entity\Category' => $categoryEntityDto,
        });

        $joins = [];
        $queryBuilder = $this->createResolveQueryBuilder();
        $queryBuilder->method('leftJoin')->willReturnCallback(static function (string $join, string $alias) use (&$joins, $queryBuilder) {
            $joins[] = [$join, $alias];

            return $queryBuilder;
        });

        $resolved = $this->entityRepository->resolveNestedAssociations($queryBuilder, $rootEntityDto, 'latestRelease.category.name');

        self::assertSame([['entity.latestRelease', 'latestRelease'], ['latestRelease.category', 'category1']], $joins);
        self::assertSame($categoryEntityDto, $resolved->getEntityDto());
        self::assertSame('category1', $resolved->getEntityAlias());
        self::assertSame('name', $resolved->getPropertyName());
    }

    public function testResolveNestedAssociationsEndingWithAssociation(): void
    {
        $categoryEntityDto = $this->createEntityDto([], ['parent' => 'App\Entity\Category'], 'App\Entity\Category');
        $rootEntityDto = $this->createEntityDto([], ['category' => 'App\Entity\Category'], 'App\Entity\Post');

        $this->entityFactory->expects(self::once())
            ->method('create')
            ->with('App\Entity\Category')
            ->willReturn($categoryEntityDto);

        $queryBuilder = $this->createResolveQueryBuilder();
        $queryBuilder->expects(self::once())
            ->method('leftJoin')
            ->with('entity.category', 'category');

        $resolved = $this->entityRepository->resolveNestedAssociations(
            $queryBuilder,
            $rootEntityDto,
            'category.parent',
            true
        );

        self::assertSame($categoryEntityDto, $resolved->getEntityDto());
        self::assertSame('category', $resolved->getEntityAlias());
        self::assertSame('parent', $resolved->getPropertyName());
    }

    public function testResolveNestedAssociationsDoesNotDuplicateJoins(): void
    {
        $authorEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\User');
        $rootEntityDto = $this->createEntityDto([], ['author' => 'App\Entity\User'], 'App\Entity\Post');

        $this->entityFactory->method('create')->willReturn($authorEntityDto);

        $queryBuilder = $this->createResolveQueryBuilder();
        $queryBuilder->expects(self::once())->method('leftJoin');

        // This is called 2 times with the same parameters on purpose to verify it's only joined once.
        $this->entityRepository->resolveNestedAssociations($queryBuilder, $rootEntityDto, 'author.name');
        $this->entityRepository->resolveNestedAssociations($queryBuilder, $rootEntityDto, 'author.name');
    }

    public function testResolveNestedAssociationsJoinsEachQueryBuilder(): void
    {
        $authorEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\User');
        $rootEntityDto = $this->createEntityDto([], ['author' => 'App\Entity\User'], 'App\Entity\Post');

        $this->entityFactory->method('create')->willReturn($authorEntityDto);

        // joined associations must not be shared between query builders: a query builder
        // created later (another request or sub-request) needs its own JOIN clauses
        $firstQueryBuilder = $this->createResolveQueryBuilder();
        $firstQueryBuilder->expects(self::once())->method('leftJoin')->with('entity.author', 'author');
        $secondQueryBuilder = $this->createResolveQueryBuilder();
        $secondQueryBuilder->expects(self::once())->method('leftJoin')->with('entity.author', 'author');

        $this->entityRepository->resolveNestedAssociations($firstQueryBuilder, $rootEntityDto, 'author.name');
        $this->entityRepository->resolveNestedAssociations($secondQueryBuilder, $rootEntityDto, 'author.name');
    }

    public function testResolveNestedAssociationsUsesTheQueryBuilderRootAlias(): void
    {
        $authorEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\User');
        $rootEntityDto = $this->createEntityDto([], ['author' => 'App\Entity\User'], 'App\Entity\Post');

        $this->entityFactory->method('create')->willReturn($authorEntityDto);

        // a custom createIndexQueryBuilder() may use a root alias different from 'entity'
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['e']);
        $queryBuilder->expects(self::once())->method('leftJoin')->with('e.author', 'author');

        $resolved = $this->entityRepository->resolveNestedAssociations($queryBuilder, $rootEntityDto, 'author.name');

        self::assertSame('author', $resolved->getEntityAlias());
    }

    public function testResolveNestedAssociationsWithoutQueryBuilderHasNoEntityAlias(): void
    {
        $authorEntityDto = $this->createEntityDto(['name' => ['type' => 'string']], [], 'App\Entity\User');
        $rootEntityDto = $this->createEntityDto([], ['author' => 'App\Entity\User'], 'App\Entity\Post');

        $this->entityFactory->method('create')->willReturn($authorEntityDto);

        // resolving with a query builder first must not leak its alias into
        // a later metadata-only resolution of the same property path
        $this->entityRepository->resolveNestedAssociations($this->createResolveQueryBuilder(), $rootEntityDto, 'author.name');
        $resolved = $this->entityRepository->resolveNestedAssociations(null, $rootEntityDto, 'author.name');

        self::assertSame($authorEntityDto, $resolved->getEntityDto());
        self::assertNull($resolved->getEntityAlias());
        self::assertSame('name', $resolved->getPropertyName());
    }

    public function testResolveNestedAssociationsThrowsOnInvalidProperty(): void
    {
        $rootEntityDto = $this->createEntityDto(['title' => ['type' => 'string']], [], 'App\Entity\Post');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "invalid" property is not valid');

        $this->entityRepository->resolveNestedAssociations(null, $rootEntityDto, 'invalid');
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

    private function createEntityDto(array $mappedFields = [], array $mappedAssociations = [], string $fqcn = 'App\Entity\Product', array $collectionAssociations = []): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasField')->willReturnCallback(static fn (string $name): bool => isset($mappedFields[$name]));
        $metadata->method('hasAssociation')->willReturnCallback(static fn (string $name): bool => isset($mappedAssociations[$name]));
        $metadata->method('isSingleValuedAssociation')->willReturnCallback(static fn (string $name): bool => isset($mappedAssociations[$name]) && !\in_array($name, $collectionAssociations, true));
        $metadata->method('getFieldNames')->willReturn(array_keys($mappedFields));
        $metadata->method('getFieldMapping')->willReturnCallback(
            static fn (string $name): array => $mappedFields[$name] ?? throw new \InvalidArgumentException()
        );
        $metadata->method('getAssociationTargetClass')->willReturnCallback(
            static fn (string $name): string => $mappedAssociations[$name] ?? throw new \InvalidArgumentException()
        );
        $metadata->fieldMappings = $mappedFields;

        return new EntityDto($fqcn, $metadata);
    }

    private function createField(string $property, bool $sortable): FieldInterface
    {
        $field = Field::new($property);
        $field->setSortable($sortable);

        return $field;
    }

    /**
     * Builds a QueryBuilder mock for resolveNestedAssociations() tests. Stubbing
     * getRootAliases() is needed because Doctrine ORM 2.x declares no native return
     * type for it, so an unstubbed mock would return null instead of [].
     */
    private function createResolveQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['entity']);

        return $queryBuilder;
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
     * Creates a stub for FormFactory using reflection since it's a final class.
     */
    private function createFormFactoryStub(): FormFactory
    {
        return (new \ReflectionClass(FormFactory::class))
            ->newInstanceWithoutConstructor();
    }

    // ORM 3.7+ receives the PHP 8.6 \SortDirection enum, older versions a string
    private static function expectedSortOrder(string $direction): \SortDirection|string
    {
        $orderParameterType = (string) (new \ReflectionMethod(QueryBuilder::class, 'addOrderBy'))->getParameters()[1]->getType();
        if (!str_contains($orderParameterType, 'SortDirection')) {
            return $direction;
        }

        return 'DESC' === $direction ? \SortDirection::Descending : \SortDirection::Ascending;
    }
}
