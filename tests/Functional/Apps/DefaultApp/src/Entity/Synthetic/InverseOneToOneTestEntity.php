<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * Entity with the inverse side of a one-to-one association, which Doctrine loads with
 * one query per row while hydrating the entity.
 */
#[ORM\Entity]
class InverseOneToOneTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne]
    private ?User $author = null;

    #[ORM\OneToOne(targetEntity: InverseOneToOneTestDetails::class, mappedBy: 'entity', cascade: ['persist'])]
    private ?InverseOneToOneTestDetails $details = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getDetails(): ?InverseOneToOneTestDetails
    {
        return $this->details;
    }

    public function setDetails(InverseOneToOneTestDetails $details): self
    {
        $this->details = $details;
        $details->setEntity($this);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
