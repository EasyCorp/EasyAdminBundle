<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

interface CrudPageConfigInterface
{
    public function getTitle(): ?string;

    public function getHelp(): ?string;

    public function setTitle(string $title): self;

    public function setHelp(string $help): self;
}
