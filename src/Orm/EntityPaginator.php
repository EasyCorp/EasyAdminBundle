<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;

final class EntityPaginator
{
    private $applicationContextProvider;
    private $crudRouter;
    private $queryBuilder;
    private $currentPage;
    private $pageSize;
    private $results;
    private $numResults;

    public function __construct(ApplicationContextProvider $applicationContextProvider, CrudUrlGenerator $crudRouter)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->crudRouter = $crudRouter;
    }

    public function paginate(QueryBuilder $queryBuilder, int $page = 1, int $pageSize = 15): self
    {
        $this->queryBuilder = $queryBuilder;
        $this->pageSize = $pageSize;
        $this->currentPage = \max(1, $page);
        $firstResult = ($this->currentPage - 1) * $this->pageSize;

        /** @var Query $query */
        $query = $this->queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($this->pageSize)
            ->getQuery();

        if (0 === \count($this->queryBuilder->getDQLPart('join'))) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        $fetchJoinCollection = $this->applicationContextProvider->getContext()->getPage()->getPaginatorFetchJoinCollection();
        $useOutputWalkers = $this->applicationContextProvider->getContext()->getPage()->getPaginatorUseOutputWalkers();

        $paginator = new Paginator($query, $fetchJoinCollection);

        if (null === $useOutputWalkers) {
            $useOutputWalkers = \count($this->queryBuilder->getDQLPart('having') ?: []) > 0;
            $paginator->setUseOutputWalkers($useOutputWalkers);
        }

        $this->results = $paginator->getIterator();
        $this->numResults = $paginator->count();

        return $this;
    }

    public function generateUrlForPage(int $page): string
    {
        return $this->crudRouter->generateWithoutReferrer(['page' => $page]);
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return (int) \ceil($this->numResults / $this->pageSize);
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
        return \max(1, $this->currentPage - 1);
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getLastPage();
    }

    public function getNextPage(): int
    {
        return \min($this->getLastPage(), $this->currentPage + 1);
    }

    public function hasToPaginate(): bool
    {
        return $this->numResults > $this->pageSize;
    }

    public function getNumResults(): int
    {
        return $this->numResults;
    }

    public function getResults(): ?\Traversable
    {
        return $this->results;
    }
}
