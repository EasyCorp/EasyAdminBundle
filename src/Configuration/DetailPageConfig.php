<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

final class DetailPageConfig
{
    private $title;
    private $help;

    public static function new(): self
    {
        return new self();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setHelp(string $help)
    {
        $this->help = $help;

        return $this;
    }
}
