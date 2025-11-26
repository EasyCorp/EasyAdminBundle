<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Developer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'favouriteProjectOf')]
    private ?Project $favouriteProject = null;

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFavouriteProject(): ?Project
    {
        return $this->favouriteProject;
    }

    public function setFavouriteProject(?Project $favouriteProject): static
    {
        $this->favouriteProject = $favouriteProject;

        return $this;
    }
}
