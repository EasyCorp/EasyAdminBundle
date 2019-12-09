<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts;

interface ItemCollectionBuilderInterface
{
    public function addItem($item): self;

    public function setItems(array $items): self;

    public function build();
}
