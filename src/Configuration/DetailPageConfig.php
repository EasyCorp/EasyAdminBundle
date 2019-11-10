<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\CrudPageContext;

final class DetailPageConfig
{
    private $title;
    private $help;

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

    public function getAsValueObject(): CrudPageContext
    {
        return CrudPageContext::newFromDetailPage($this->title, $this->help);
    }
}
