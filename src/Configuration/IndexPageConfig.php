<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;

final class IndexPageConfig
{
    private $pageName = 'index';
    private $title;
    private $help;
    private $defaultSort = [];
    private $entityViewPermission;
    private $searchProperties = [];
    private $paginatorPageSize = 15;
    private $paginatorFetchJoinCollection = true;
    private $paginatorUseOutputWalkers;
    private $filters = null;

    public static function new(): self
    {
        return new self();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setHelp(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    /**
     * @param array $sortAndOrder ['propertyName' => 'ASC|DESC', ...]
     */
    public function setDefaultSort(array $sortAndOrder): self
    {
        $sortAndOrder = array_map('strtoupper', $sortAndOrder);
        foreach ($sortAndOrder as $sortField => $sortOrder) {
            if (!\in_array($sortOrder, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(sprintf('The sort order can be only "ASC" or "DESC", "%s" given.', $sortOrder));
            }

            if (!\is_string($sortField)) {
                throw new \InvalidArgumentException(sprintf('The keys of the array that defines the default sort must be strings with the property names, but the given "%s" value is a "%s".', $sortField, gettype($sortField)));
            }
        }

        $this->defaultSort = $sortAndOrder;

        return $this;
    }

    public function setEntityViewPermission(string $permission): self
    {
        $this->entityViewPermission = $permission;

        return $this;
    }

    public function setSearchProperties(?array $propertyNames): self
    {
        $this->searchProperties = $propertyNames;

        return $this;
    }

    public function setPaginatorPageSize(int $maxResultsPerPage): self
    {
        if ($maxResultsPerPage < 1) {
            throw new \InvalidArgumentException(sprintf('The minimum value of paginator page size is 1.'));
        }

        $this->paginatorPageSize = $maxResultsPerPage;

        return $this;
    }

    public function setPaginatorFetchJoinCollection(bool $fetchJoinCollection): self
    {
        $this->paginatorFetchJoinCollection = $fetchJoinCollection;

        return $this;
    }

    public function setPaginatorUseOutputWalkers(bool $useOutputWalkers): self
    {
        $this->paginatorUseOutputWalkers = $useOutputWalkers;

        return $this;
    }

    public function setFilters(?array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function getAsDto(): CrudPageDto
    {
        return CrudPageDto::newFromIndexPage($this->pageName, $this->title, $this->help, $this->defaultSort, $this->entityViewPermission, $this->searchProperties, $this->filters, new PaginatorDto($this->paginatorPageSize, $this->paginatorFetchJoinCollection, $this->paginatorUseOutputWalkers));
    }
}
