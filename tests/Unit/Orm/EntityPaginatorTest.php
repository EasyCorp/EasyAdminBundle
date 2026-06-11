<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Orm;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class EntityPaginatorTest extends TestCase
{
    /**
     * @testWith [5, 5]
     *           [-1, 1]
     *           [0, 1]
     */
    public function testGetCurrentPage(int $pageNumber, int $expectedPage): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: $pageNumber, pageSize: 10, totalResults: 100);

        $this->assertSame($expectedPage, $paginator->getCurrentPage());
    }

    /**
     * @testWith [95, 10]
     *           [100, 10]
     *           [5, 1]
     *           [0, 1]
     */
    public function testGetLastPage(int $totalResults, int $expectedLastPage): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: $totalResults);

        $this->assertSame($expectedLastPage, $paginator->getLastPage());
    }

    public function testGetPageSize(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 25, totalResults: 100);

        $this->assertSame(25, $paginator->getPageSize());
    }

    public function testHasPreviousPageOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->hasPreviousPage());
    }

    public function testHasPreviousPageOnSecondPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 2, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->hasPreviousPage());
    }

    public function testGetPreviousPageOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertSame(1, $paginator->getPreviousPage());
    }

    public function testGetPreviousPageOnMiddlePage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 10, totalResults: 100);

        $this->assertSame(4, $paginator->getPreviousPage());
    }

    public function testHasNextPageOnLastPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 10, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->hasNextPage());
    }

    public function testHasNextPageOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->hasNextPage());
    }

    public function testGetNextPageOnLastPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 10, pageSize: 10, totalResults: 100);

        $this->assertSame(10, $paginator->getNextPage());
    }

    public function testGetNextPageOnMiddlePage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 10, totalResults: 100);

        $this->assertSame(6, $paginator->getNextPage());
    }

    public function testHasToPaginateWithManyResults(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->hasToPaginate());
    }

    public function testHasToPaginateWithFewResults(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 5);

        $this->assertFalse($paginator->hasToPaginate());
    }

    public function testHasToPaginateWithExactPageSize(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 10);

        $this->assertFalse($paginator->hasToPaginate());
    }

    public function testIsOutOfRangeOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->isOutOfRange());
    }

    public function testIsOutOfRangeOnValidMiddlePage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 10, totalResults: 100);

        $this->assertFalse($paginator->isOutOfRange());
    }

    public function testIsOutOfRangeOnPageBeyondLast(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 15, pageSize: 10, totalResults: 100);

        $this->assertTrue($paginator->isOutOfRange());
    }

    public function testIsOutOfRangeWithEmptyResultsOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 0);

        $this->assertFalse($paginator->isOutOfRange());
    }

    public function testGetNumResults(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 10, totalResults: 135);

        $this->assertSame(135, $paginator->getNumResults());
    }

    public function testGetRangeFirstResultNumber(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 3, pageSize: 20, totalResults: 100);

        $this->assertSame(41, $paginator->getRangeFirstResultNumber());
    }

    public function testGetRangeFirstResultNumberOnFirstPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 1, pageSize: 20, totalResults: 100);

        $this->assertSame(1, $paginator->getRangeFirstResultNumber());
    }

    public function testGetRangeLastResultNumber(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 2, pageSize: 20, totalResults: 100);

        $this->assertSame(40, $paginator->getRangeLastResultNumber());
    }

    public function testGetRangeLastResultNumberOnLastPage(): void
    {
        $paginator = $this->createPaginatedPaginator(pageNumber: 5, pageSize: 20, totalResults: 95);

        $this->assertSame(95, $paginator->getRangeLastResultNumber());
    }

    /**
     * @dataProvider pageRangeDataProvider
     */
    public function testGetPageRange(int $currentPage, int $lastPage, int $pagesOnEachSide, int $pagesOnEdges, array $expectedRange): void
    {
        $pageSize = 10;
        $totalResults = $lastPage * $pageSize;

        $paginator = $this->createPaginatedPaginator(
            pageNumber: $currentPage,
            pageSize: $pageSize,
            totalResults: $totalResults,
            rangeSize: $pagesOnEachSide,
            rangeEdgeSize: $pagesOnEdges
        );

        $range = iterator_to_array($paginator->getPageRange(), false);

        $this->assertSame($expectedRange, $range);
    }

    public static function pageRangeDataProvider(): iterable
    {
        // small paginator - all pages shown without gaps
        yield 'small paginator with 5 pages, current page 1' => [
            'currentPage' => 1,
            'lastPage' => 5,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5],
        ];

        yield 'small paginator with 5 pages, current page 3' => [
            'currentPage' => 3,
            'lastPage' => 5,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5],
        ];

        // large paginator - gaps should appear
        yield 'large paginator, current page at start' => [
            'currentPage' => 1,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, null, 35],
        ];

        yield 'large paginator, current page 2' => [
            'currentPage' => 2,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5, null, 35],
        ];

        yield 'large paginator, current page in the middle' => [
            'currentPage' => 18,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 15, 16, 17, 18, 19, 20, 21, null, 35],
        ];

        yield 'large paginator, current page at end' => [
            'currentPage' => 35,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 32, 33, 34, 35],
        ];

        yield 'large paginator, current page near end' => [
            'currentPage' => 34,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 31, 32, 33, 34, 35],
        ];

        // edge case: pagesOnEachSide = 0 returns null
        yield 'pagesOnEachSide is zero' => [
            'currentPage' => 5,
            'lastPage' => 10,
            'pagesOnEachSide' => 0,
            'pagesOnEdges' => 1,
            'expectedRange' => [],
        ];

        // edge case: single page
        yield 'single page' => [
            'currentPage' => 1,
            'lastPage' => 1,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1],
        ];

        // different pagesOnEdges values
        yield 'large paginator with 2 pages on edges' => [
            'currentPage' => 15,
            'lastPage' => 30,
            'pagesOnEachSide' => 2,
            'pagesOnEdges' => 2,
            'expectedRange' => [1, 2, null, 13, 14, 15, 16, 17, null, 29, 30],
        ];

        // transition zone - where current page is near the threshold
        yield 'near start threshold' => [
            'currentPage' => 5,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, 2, 3, 4, 5, 6, 7, 8, null, 35],
        ];

        yield 'near end threshold' => [
            'currentPage' => 31,
            'lastPage' => 35,
            'pagesOnEachSide' => 3,
            'pagesOnEdges' => 1,
            'expectedRange' => [1, null, 28, 29, 30, 31, 32, 33, 34, 35],
        ];
    }

    public function testGetPageRangeWithCustomParameters(): void
    {
        $paginator = $this->createPaginatedPaginator(
            pageNumber: 10,
            pageSize: 10,
            totalResults: 200,
            rangeSize: 3,
            rangeEdgeSize: 1
        );

        // call with custom parameters that override defaults
        $range = iterator_to_array($paginator->getPageRange(pagesOnEachSide: 2, pagesOnEdges: 2), false);

        $this->assertSame([1, 2, null, 8, 9, 10, 11, 12, null, 19, 20], $range);
    }

    private function createPaginatedPaginator(int $pageNumber, int $pageSize, int $totalResults, int $rangeSize = 3, int $rangeEdgeSize = 1): EntityPaginator
    {
        // create an EntityPaginator without calling its constructor (avoid mocking final classes)
        $reflection = new \ReflectionClass(EntityPaginator::class);
        $paginator = $reflection->newInstanceWithoutConstructor();

        $currentPageProp = $reflection->getProperty('currentPage');
        $currentPageProp->setValue($paginator, max(1, $pageNumber));

        $pageSizeProp = $reflection->getProperty('pageSize');
        $pageSizeProp->setValue($paginator, $pageSize);

        $numResultsProp = $reflection->getProperty('numResults');
        $numResultsProp->setValue($paginator, $totalResults);

        $rangeSizeProp = $reflection->getProperty('rangeSize');
        $rangeSizeProp->setValue($paginator, $rangeSize);

        $rangeEdgeSizeProp = $reflection->getProperty('rangeEdgeSize');
        $rangeEdgeSizeProp->setValue($paginator, $rangeEdgeSize);

        $rangeFirstResultNumberProp = $reflection->getProperty('rangeFirstResultNumber');
        $rangeFirstResultNumberProp->setValue($paginator, $pageSize * (max(1, $pageNumber) - 1) + 1);

        $rangeLastResultNumberProp = $reflection->getProperty('rangeLastResultNumber');
        $rangeLastResultNumber = $pageSize * max(1, $pageNumber);
        if ($rangeLastResultNumber > $totalResults) {
            $rangeLastResultNumber = $totalResults;
        }
        $rangeLastResultNumberProp->setValue($paginator, $rangeLastResultNumber);

        return $paginator;
    }

    public function testGetResultsAsJsonWithDefaultToString(): void
    {
        $entity = new class {
            public int $id = 123;

            public function __toString(): string
            {
                return 'Test Entity';
            }
        };

        $paginator = $this->createPaginatorWithResults([$entity]);
        $json = $paginator->getResultsAsJson();
        $result = json_decode($json, true);

        $this->assertArrayHasKey('results', $result);
        $this->assertCount(1, $result['results']);
        $this->assertSame('Test Entity', $result['results'][0]['entityAsString']);
    }

    public function testGetResultsAsJsonWithCallback(): void
    {
        $entity = new class {
            public int $id = 42;

            public function getId(): int
            {
                return $this->id;
            }

            public function getName(): string
            {
                return 'Product';
            }

            public function __toString(): string
            {
                return 'Should not be used';
            }
        };

        $callback = static fn ($e): string => sprintf('[%d] %s', $e->getId(), $e->getName());

        $paginator = $this->createPaginatorWithResults([$entity]);
        $json = $paginator->getResultsAsJson($callback);
        $result = json_decode($json, true);

        $this->assertSame('[42] Product', $result['results'][0]['entityAsString']);
    }

    public function testGetResultsAsJsonWithCallbackEscapesHtmlByDefault(): void
    {
        $entity = new class {
            public int $id = 1;

            public function getName(): string
            {
                return '<script>alert("XSS")</script>';
            }

            public function __toString(): string
            {
                return 'Safe';
            }
        };

        $callback = static fn ($e): string => $e->getName();

        $paginator = $this->createPaginatorWithResults([$entity]);
        $json = $paginator->getResultsAsJson($callback);
        $result = json_decode($json, true);

        $escapedValue = $result['results'][0]['entityAsString'];
        $this->assertStringContainsString('&lt;script&gt;', $escapedValue);
        $this->assertStringNotContainsString('<script>', $escapedValue);
    }

    public function testGetResultsAsJsonWithCallbackRendersHtmlWhenEnabled(): void
    {
        $entity = new class {
            public int $id = 1;

            public function getName(): string
            {
                return '<strong>Bold Text</strong>';
            }

            public function __toString(): string
            {
                return 'Safe';
            }
        };

        $callback = static fn ($e): string => $e->getName();

        $paginator = $this->createPaginatorWithResults([$entity]);
        $json = $paginator->getResultsAsJson($callback, null, true);
        $result = json_decode($json, true);

        $htmlValue = $result['results'][0]['entityAsString'];
        $this->assertStringContainsString('<strong>Bold Text</strong>', $htmlValue);
        $this->assertStringNotContainsString('&lt;', $htmlValue);
    }

    public function testGetResultsAsJsonWithTemplateEscapesHtmlByDefault(): void
    {
        $entity = new class {
            public int $id = 1;

            public function getName(): string
            {
                return 'Test Product';
            }
        };

        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willReturn('<strong>Test Product</strong>');

        $paginator = $this->createPaginatorWithResults([$entity], $twig);
        $json = $paginator->getResultsAsJson(null, 'test_template.html.twig');
        $result = json_decode($json, true);

        $escapedValue = $result['results'][0]['entityAsString'];
        $this->assertStringContainsString('&lt;strong&gt;', $escapedValue);
        $this->assertStringNotContainsString('<strong>', $escapedValue);
    }

    public function testGetResultsAsJsonWithTemplateRendersHtmlWhenEnabled(): void
    {
        $entity = new class {
            public int $id = 1;

            public function getName(): string
            {
                return 'Test Product';
            }
        };

        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willReturn('<div class="product"><strong>Test Product</strong></div>');

        $paginator = $this->createPaginatorWithResults([$entity], $twig);
        $json = $paginator->getResultsAsJson(null, 'test_template.html.twig', true);
        $result = json_decode($json, true);

        $htmlValue = $result['results'][0]['entityAsString'];
        $this->assertStringContainsString('<div class="product">', $htmlValue);
        $this->assertStringContainsString('<strong>Test Product</strong>', $htmlValue);
    }

    public function testGetResultsAsJsonTemplateReceivesEntityVariable(): void
    {
        $entity = new class {
            public int $id = 1;

            public function getName(): string
            {
                return 'Test Product';
            }
        };

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('test.html.twig', ['entity' => $entity])
            ->willReturn('Rendered');

        $paginator = $this->createPaginatorWithResults([$entity], $twig);
        $paginator->getResultsAsJson(null, 'test.html.twig');
    }

    public function testGetResultsAsJsonTemplatePriorityOverCallback(): void
    {
        $entity = new class {
            public int $id = 1;

            public function __toString(): string
            {
                return 'ToString';
            }
        };

        $callback = static fn ($e): string => 'Callback';

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('Template');

        $paginator = $this->createPaginatorWithResults([$entity], $twig);
        $json = $paginator->getResultsAsJson($callback, 'test.html.twig');
        $result = json_decode($json, true);

        // template should take priority over callback
        $this->assertSame('Template', $result['results'][0]['entityAsString']);
    }

    public function testGetResultsAsJsonThrowsExceptionOnTemplateError(): void
    {
        $entity = new class {
            public int $id = 1;

            public function __toString(): string
            {
                return 'Test';
            }
        };

        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->willThrowException(new \Twig\Error\RuntimeError('Template error'));

        $paginator = $this->createPaginatorWithResults([$entity], $twig);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error rendering autocomplete template');
        $paginator->getResultsAsJson(null, 'broken_template.html.twig');
    }

    public function testGetResultsAsJsonHandlesMultipleEntities(): void
    {
        $entity1 = new class {
            public int $id = 1;

            public function getName(): string
            {
                return 'Entity 1';
            }

            public function __toString(): string
            {
                return $this->getName();
            }
        };

        $entity2 = new class {
            public int $id = 2;

            public function getName(): string
            {
                return 'Entity 2';
            }

            public function __toString(): string
            {
                return $this->getName();
            }
        };

        $callback = static fn ($e): string => 'Custom: '.$e->getName();

        $paginator = $this->createPaginatorWithResults([$entity1, $entity2]);
        $json = $paginator->getResultsAsJson($callback);
        $result = json_decode($json, true);

        $this->assertCount(2, $result['results']);
        $this->assertSame('Custom: Entity 1', $result['results'][0]['entityAsString']);
        $this->assertSame('Custom: Entity 2', $result['results'][1]['entityAsString']);
    }

    private function createPaginatorWithResults(array $entities, ?Environment $twig = null): EntityPaginator
    {
        $adminUrlGenerator = $this->createMock(AdminUrlGeneratorInterface::class);
        $adminUrlGenerator->method('set')->willReturnSelf();
        $adminUrlGenerator->method('generateUrl')->willReturn('http://example.com/next');

        // create EntityFactory with minimal mock dependencies
        // note: When first param is not FieldFactory, constructor uses backwards-compatible signature
        // where param1 = authChecker, param2 = doctrine, param3 = eventDispatcher
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->isIdentifierComposite = false;
        $classMetadata->method('getIdentifierFieldNames')->willReturn(['id']);
        $classMetadata->method('getSingleIdentifierFieldName')->willReturn('id');

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->method('getClassMetadata')->willReturn($classMetadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerForClass')->willReturn($objectManager);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $entityFactory = new EntityFactory($authChecker, $doctrine, $eventDispatcher);

        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        if (null === $twig) {
            $twig = $this->createMock(Environment::class);
        }

        $reflection = new \ReflectionClass(EntityPaginator::class);
        $paginator = $reflection->newInstanceArgs([
            $adminUrlGenerator,
            $entityFactory,
            $requestStack,
            $twig,
        ]);

        // set the results
        $resultsProp = $reflection->getProperty('results');
        $resultsProp->setValue($paginator, $entities);

        // set other required properties
        $currentPageProp = $reflection->getProperty('currentPage');
        $currentPageProp->setValue($paginator, 1);

        $pageSizeProp = $reflection->getProperty('pageSize');
        $pageSizeProp->setValue($paginator, 15);

        $numResultsProp = $reflection->getProperty('numResults');
        $numResultsProp->setValue($paginator, \count($entities));

        return $paginator;
    }
}
