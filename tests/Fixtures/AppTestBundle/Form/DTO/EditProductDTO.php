<?php

namespace AppTestBundle\Form\DTO;

use AppTestBundle\Entity\UnitTests\Product;

class EditProductDTO
{
    private $title;

    public function __construct(Product $product)
    {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
