<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Search;

use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Paginator
{
    private const PAGE_SIZE = 15;
    private $currentPage;
    private $pageSize;
    private $results;
    private $numResults;

    public function create(DoctrineQueryBuilder $queryBuilder, int $currentPage = 1, int $pageSize = self::PAGE_SIZE): Paginator
    {
        $this->currentPage = max(1, $currentPage);
        $this->pageSize = $pageSize;
        $firstResult = ($this->currentPage - 1) * $pageSize;

        $query = $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($pageSize)
            ->getQuery();

        if (0 === \count($queryBuilder->getDQLPart('join'))) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        $paginator = new DoctrinePaginator($query, true);
        $paginator->setUseOutputWalkers(false);

        $this->results = $paginator->getIterator();
        $this->numResults = $paginator->count();

        return $this;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getNbPages(): int
    {
        @trigger_error(sprintf('The "%s" method is deprecated. Use "getLastPage()" instead.', __METHOD__), E_USER_DEPRECATED);

        return $this->getLastPage();
    }

    public function getLastPage(): int
    {
        return (int) ceil($this->numResults / $this->pageSize);
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

    public function getNbResults(): int
    {
        @trigger_error(sprintf('The "%s" method is deprecated. Use "getNumResults()" instead.', __METHOD__), E_USER_DEPRECATED);

        return $this->getNumResults();
    }

    public function getNumResults(): int
    {
        return $this->numResults;
    }

    public function getResults(): ?\Traversable
    {
        return $this->results;
    }

    public function getCurrentPageResults(): ?\Traversable
    {
        @trigger_error(sprintf('The "%s" method is deprecated. Use "getResults()" instead.', __METHOD__), E_USER_DEPRECATED);

        return $this->getResults();
    }

    /**
     * Creates a Doctrine ORM paginator for the given query builder.
     *
     * @param DoctrineQuery|DoctrineQueryBuilder $queryBuilder
     * @param int                                $page
     * @param int                                $maxPerPage
     *
     * @return \Traversable
     */
    public function createOrmPaginator($queryBuilder, $page = 1, $maxPerPage = self::PAGE_SIZE)
    {
        @trigger_error(sprintf('The "%s" method is deprecated. Use "new %s()" instead to create the paginator.', __METHOD__, __CLASS__), E_USER_DEPRECATED);

        return new self($queryBuilder, $page, $maxPerPage);
    }
}
