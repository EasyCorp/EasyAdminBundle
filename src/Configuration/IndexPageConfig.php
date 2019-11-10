<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\CrudPageContext;

final class IndexPageConfig
{
    private $title;
    private $help;
    private $maxResults = 30;
    private $searchFields;
    private $filters;

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

    public function setMaxResults(int $maxResults): self
    {
        if ($maxResults < 1) {
            throw new \InvalidArgumentException(sprintf('The minimum value of the maxResults option is 1.'));
        }

        $this->maxResults = $maxResults;

        return $this;
    }

    public function setSearchFields(array $fieldNames): self
    {
        $this->searchFields = $fieldNames;

        return $this;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function getAsValueObject(): CrudPageContext
    {
        return CrudPageContext::newFromIndexPage($this->title, $this->help, $this->maxResults, $this->searchFields, $this->filters);
    }
}
