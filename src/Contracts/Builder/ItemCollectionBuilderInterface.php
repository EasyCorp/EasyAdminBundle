<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Builder;

interface ItemCollectionBuilderInterface
{
    public function addItem($item): self;

    public function setItems(array $items): self;

    public function create();
}
