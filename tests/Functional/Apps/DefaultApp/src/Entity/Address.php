<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Address
{
    #[ORM\Column(nullable: true)]
    private ?int $street = null;

    #[ORM\Column(nullable: true)]
    private ?string $city = null;

    public function getStreet(): ?int
    {
        return $this->street;
    }

    public function setStreet(?int $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }
}
