<?php

namespace AppTestBundle\Form\DTO;

class NewProductDTO
{
    private $skud;

    private $title;

    public function getSkud(): ?string
    {
        return $this->skud;
    }

    public function setSkud(?string $skud): void
    {
        $this->skud = $skud;
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
