<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\FieldInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

final class SearchDto
{
    private $defaultSort;
    private $customSort;
    private $query;
    /** @var FieldInterface[] */
    private $fields;
    /** @var string[]|null */
    private $searchFields;

    public function __construct(ParameterBag $queryParams, array $defaultSort, array $fields, ?array $searchFields)
    {
        $this->defaultSort = $defaultSort;
        $this->customSort = $queryParams->get('sort', []);
        $this->query = $queryParams->get('query');
        $this->fields = $fields;
        $this->searchFields = $searchFields;
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

        $fields = [];
        foreach ($this->fields as $field) {
            if (in_array($field->getProperty(), $this->searchFields, true)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }
}
