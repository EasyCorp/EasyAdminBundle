<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Dto\TemplateDto;

final class TemplateDtoCollection implements \IteratorAggregate
{
    /** @var TemplateDto[] */
    private $templates;

    private function __construct()
    {
    }

    public static function new(): self
    {
        $collection = new self();
        $collection->templates = [];

        return $collection;
    }

    public function setTemplate($templateName, $templatePath): self
    {
        $templateDto = new TemplateDto($templateName, $templatePath);
        $this->templates[$templateName] = $templateDto;

        return $this;
    }

    public function setTemplates(array $templateNamesAndPaths): self
    {
        $this->templates = [];
        foreach ($templateNamesAndPaths as $templateName => $templatePath) {
            $this->setTemplate($templateName, $templatePath);
        }

        return $this;
    }

    /**
     * @return TemplateDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->templates);
    }
}
