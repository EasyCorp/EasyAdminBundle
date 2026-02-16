<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for testing search functionality in EasyAdmin.
 */
#[ORM\Entity]
class SearchTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $searchableTextField = null;

    #[ORM\Column(type: 'text')]
    private ?string $searchableContentField = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nonSearchableField = null;

    /**
     * ManyToOne relationship for testing search in associations.
     */
    #[ORM\ManyToOne(targetEntity: SearchTestAuthor::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SearchTestAuthor $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSearchableTextField(): ?string
    {
        return $this->searchableTextField;
    }

    public function setSearchableTextField(?string $searchableTextField): self
    {
        $this->searchableTextField = $searchableTextField;

        return $this;
    }

    public function getSearchableContentField(): ?string
    {
        return $this->searchableContentField;
    }

    public function setSearchableContentField(?string $searchableContentField): self
    {
        $this->searchableContentField = $searchableContentField;

        return $this;
    }

    public function getNonSearchableField(): ?string
    {
        return $this->nonSearchableField;
    }

    public function setNonSearchableField(?string $nonSearchableField): self
    {
        $this->nonSearchableField = $nonSearchableField;

        return $this;
    }

    public function getAuthor(): ?SearchTestAuthor
    {
        return $this->author;
    }

    public function setAuthor(?SearchTestAuthor $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function __toString(): string
    {
        return $this->searchableTextField ?? '';
    }
}
