<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\EntityFactory;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Address
{
    #[ORM\Column]
    private int $street;

    #[ORM\Column]
    private string $city;

    public function getStreet(): int
    {
        return $this->street;
    }

    public function setStreet(int $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }
}
