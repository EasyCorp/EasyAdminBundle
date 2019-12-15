<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use Symfony\Component\HttpFoundation\Request;

final class SearchDto
{
    private $request;
    private $defaultSort;
    private $customSort;
    private $query;
    /** @var string[]|null */
    private $searchProperties;
    /** @var string[]|null */
    private $filters;
    private $allEntityProperties;

    public function __construct(Request $request, CrudPageDto $crudPageDto, EntityDto $entityDto)
    {
        $this->request = $request;
        $this->defaultSort = $crudPageDto->getDefaultSort();
        $this->customSort = $request->query->get('sort', []);
        $this->query = $request->query->get('query');
        $this->searchProperties = $crudPageDto->getSearchFields();
        $this->filters = $crudPageDto->getFilters();
        $this->allEntityProperties = $entityDto->getDefinedPropertiesNames();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getSort(): array
    {
        // we can't use an array_merge() call because $customSort has more priority
        // than $defaultSort, so the default sort must only be applied if there's
        // not already a custom sort config for the same property
        $mergedSort = $this->customSort;
        foreach ($this->defaultSort as $propertyName => $order) {
            if (!array_key_exists($propertyName, $mergedSort)) {
                $mergedSort[$propertyName] = $order;
            }
        }

        return $mergedSort;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return string[]
     */
    public function getSearchableProperties(): array
    {
        if (empty($this->searchProperties)) {
            return $this->allEntityProperties;
        }

        return $this->searchProperties;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }
}
