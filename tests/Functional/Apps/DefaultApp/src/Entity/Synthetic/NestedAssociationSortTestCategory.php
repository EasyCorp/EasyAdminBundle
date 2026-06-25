<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

/**
 * Category entity for nested association sort testing. Self-references via `parent`
 * so a root entity can be sorted by `category.parent.name`.
 */
#[ORM\Entity]
class NestedAssociationSortTestCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?NestedAssociationSortTestCategory $parent = null;

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

    public function getParent(): ?NestedAssociationSortTestCategory
    {
        return $this->parent;
    }

    public function setParent(?NestedAssociationSortTestCategory $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
