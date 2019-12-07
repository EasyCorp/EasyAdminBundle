<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

final class SearchDto
{
    private $request;
    private $defaultSort;
    private $customSort;
    private $query;
    /** @var FieldInterface[] */
    private $fields;
    /** @var string[]|null */
    private $searchFields;
    /** @var string[]|null */
    private $filters;

    public function __construct(ApplicationContext $applicationContext, array $fields)
    {
        $this->request = $request = $applicationContext->getRequest();
        $this->defaultSort = $applicationContext->getPage()->getDefaultSort();
        $this->customSort = $request->query->get('sort', []);
        $this->query = $request->query->get('query');
        $this->searchFields = $applicationContext->getPage()->getSearchFields();
        $this->filters = $applicationContext->getPage()->getFilters();
        $this->fields = $fields;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getSort(): array
    {
        // we can't use an array_merge() call because $customSort has more priority
        // than $defaultSort, so the default sort must only be applied if there's
        // not already a custom sort config for the same field
        $mergedSort = $this->customSort;
        foreach ($this->defaultSort as $fieldName => $order) {
            if (!array_key_exists($fieldName, $mergedSort)) {
                $mergedSort[$fieldName] = $order;
            }
        }

        return $mergedSort;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return FieldInterface[]
     */
    public function getSearchableFields(): array
    {
        if (empty($this->searchFields)) {
            return $this->fields;
        }

        // TODO: check the 'permission' of the field before using it
        $fields = [];
        foreach ($this->fields as $field) {
            if (in_array($field->getProperty(), $this->searchFields, true)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }
}
