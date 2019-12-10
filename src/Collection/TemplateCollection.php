<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

final class TemplateCollection implements \IteratorAggregate
{
    private $templateNamesAndPaths;

    private function __construct()
    {
    }

    public static function new(): self
    {
        $collection = new self();
        $collection->templateNamesAndPaths = [];

        return $collection;
    }

    public function setTemplate(string $templateName, string $templatePath): self
    {
        $this->templateNamesAndPaths[$templateName] = $templatePath;
    }

    public function setTemplates(array $templateNamesAndPaths): self
    {
        $this->templateNamesAndPaths = $templateNamesAndPaths;
    }

    /**
     * @return string[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->templateNamesAndPaths);
    }
}
