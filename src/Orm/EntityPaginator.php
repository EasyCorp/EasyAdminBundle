<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityPaginatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityPaginator implements EntityPaginatorInterface
{
    private $adminUrlGenerator;
    private $entityFactory;
    private $currentPage;
    private $pageSize;
    private $rangeSize;
    private $rangeEdgeSize;
    private $results;
    private $numResults;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, EntityFactory $entityFactory)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->entityFactory = $entityFactory;
    }

    public function paginate(PaginatorDto $paginatorDto, QueryBuilder $queryBuilder): EntityPaginatorInterface
    {
        $this->pageSize = $paginatorDto->getPageSize();
        $this->rangeSize = $paginatorDto->getRangeSize();
        $this->rangeEdgeSize = $paginatorDto->getRangeEdgeSize();
        $this->currentPage = max(1, $paginatorDto->getPageNumber());
        $firstResult = ($this->currentPage - 1) * $this->pageSize;

        /** @var Query $query */
        $query = $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($this->pageSize)
            ->getQuery();

        if (0 === \count($queryBuilder->getDQLPart('join'))) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        $paginator = new Paginator($query, $paginatorDto->fetchJoinCollection());

        if (null === $useOutputWalkers = $paginatorDto->useOutputWalkers()) {
            $havingPart = $queryBuilder->getDQLPart('having');
            if (\is_array($havingPart)) {
                $useOutputWalkers = \count($havingPart) > 0;
            } else {
                $useOutputWalkers = null !== $havingPart;
            }
        }
        $paginator->setUseOutputWalkers($useOutputWalkers);

        $this->results = $paginator->getIterator();
        $this->numResults = $paginator->count();

        return $this;
    }

    public function generateUrlForPage(int $page): string
    {
        return $this->adminUrlGenerator->set(EA::PAGE, $page)->includeReferrer()->generateUrl();
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return (int) ceil($this->numResults / $this->pageSize);
    }

    /**
     * It returns the closest available pages around the current page.
     * E.g. a paginator with 35 pages, if current page = 1, returns [1, 2, 3, 4, null, 35]
     *      if current page = 18, returns [1, null, 15, 16, 17, 18, 19, 20, 21, null, 35]
     * NULL values mean a gap in the pagination (they can be represented as ellipsis in the templates).
     *
     * This code was inspired by https://github.com/django/django/blob/55fabc53373a8c7ef31d8c4cffd2a07be0a88c2e/django/core/paginator.py#L134
     * (c) Django Project
     *
     * @return int[]
     */
    public function getPageRange(int $pagesOnEachSide = null, int $pagesOnEdges = null): iterable
    {
        $pagesOnEachSide = $pagesOnEachSide ?? $this->rangeSize;
        $pagesOnEdges = $pagesOnEdges ?? $this->rangeEdgeSize;

        if (0 === $pagesOnEachSide) {
            return null;
        }

        if ($this->getLastPage() <= ($pagesOnEachSide + $pagesOnEdges) * 2) {
            return yield from range(1, $this->getLastPage());
        }

        if ($this->getCurrentPage() > ($pagesOnEachSide + $pagesOnEdges + 1)) {
            yield from range(1, $pagesOnEdges);
            yield null;
            yield from range($this->getCurrentPage() - $pagesOnEachSide, $this->getCurrentPage());
        } else {
            yield from range(1, $this->getCurrentPage());
        }

        if ($this->getCurrentPage() < ($this->getLastPage() - $pagesOnEachSide - $pagesOnEdges - 1)) {
            yield from range($this->getCurrentPage() + 1, $this->getCurrentPage() + $pagesOnEachSide);
            yield null;
            yield from range($this->getLastPage() - $pagesOnEdges + 1, $this->getLastPage());
        } elseif ($this->getCurrentPage() + 1 <= $this->getLastPage()) {
            yield from range($this->getCurrentPage() + 1, $this->getLastPage());
        }
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getLastPage();
    }

    public function getNextPage(): int
    {
        return min($this->getLastPage(), $this->currentPage + 1);
    }

    public function hasToPaginate(): bool
    {
        return $this->numResults > $this->pageSize;
    }

    public function isOutOfRange(): bool
    {
        if (1 === $this->currentPage) {
            return false;
        }

        return $this->currentPage < 1 || $this->currentPage > $this->getLastPage();
    }

    public function getNumResults(): int
    {
        return $this->numResults;
    }

    public function getResults(): ?iterable
    {
        return $this->results;
    }

    public function getResultsAsJson(): string
    {
        foreach ($this->getResults() ?? [] as $entityInstance) {
            $entityDto = $this->entityFactory->createForEntityInstance($entityInstance);

            $jsonResult['results'][] = [
                EA::ENTITY_ID => $entityDto->getPrimaryKeyValueAsString(),
                'entityAsString' => $entityDto->toString(),
            ];
        }

        $nextPageUrl = !$this->hasNextPage() ? null : $this->adminUrlGenerator->set(EA::PAGE, $this->getNextPage())->removeReferrer()->generateUrl();
        $jsonResult['next_page'] = $nextPageUrl;

        return json_encode($jsonResult);
    }
}
