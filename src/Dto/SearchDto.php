<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Collection\PropertyDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\PropertyInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

final class SearchDto
{
    private $request;
    private $defaultSort;
    private $customSort;
    private $query;
    /** @var PropertyDtoCollection */
    private $properties;
    /** @var string[]|null */
    private $searchProperties;
    /** @var string[]|null */
    private $filters;

    public function __construct(ApplicationContext $applicationContext, PropertyDtoCollection $properties)
    {
        $this->request = $request = $applicationContext->getRequest();
        $this->defaultSort = $applicationContext->getPage()->getDefaultSort();
        $this->customSort = $request->query->get('sort', []);
        $this->query = $request->query->get('query');
        $this->searchProperties = $applicationContext->getPage()->getSearchFields();
        $this->filters = $applicationContext->getPage()->getFilters();
        $this->properties = $properties;
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

    public function getSearchableProperties(): PropertyDtoCollection
    {
        if (empty($this->searchProperties)) {
            return $this->properties;
        }

        // TODO: check the 'permission' of the field before using it
        $propertiesDto = [];
        foreach ($this->properties as $propertyDto) {
            if (in_array($propertyDto->getName(), $this->searchProperties, true)) {
                $propertiesDto[] = $propertyDto;
            }
        }

        return $propertiesDto;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }
}
