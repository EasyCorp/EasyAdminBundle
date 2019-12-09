<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EasyCorp\Bundle\EasyAdminBundle\Builder\EntityViewBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityViewDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;

final class EntityPaginator
{
    private $crudRouter;
    private $currentPage;
    private $pageSize;
    private $results;
    private $numResults;

    public function __construct(CrudUrlGenerator $crudRouter)
    {
        $this->crudRouter = $crudRouter;
    }

    public function paginate(PaginatorDto $paginatorDto, QueryBuilder $queryBuilder): self
    {
        $this->pageSize = $paginatorDto->getPageSize();
        $this->currentPage = \max(1, $paginatorDto->getPageNumber());
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
            $useOutputWalkers = \count($queryBuilder->getDQLPart('having') ?: []) > 0;
        }
        $paginator->setUseOutputWalkers($useOutputWalkers);

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

    public function getResults(): ?iterable
    {
        return $this->results;
    }
}
