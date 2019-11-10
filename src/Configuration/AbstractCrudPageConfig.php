<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

abstract class AbstractCrudPageConfig implements CrudPageConfigInterface
{
    protected $title;
    protected $help;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setTitle(string $title): CrudPageConfigInterface
    {
        $this->title = $title;

        return $this;
    }

    public function setHelp(string $help): CrudPageConfigInterface
    {
        $this->help = $help;

        return $this;
    }
}
