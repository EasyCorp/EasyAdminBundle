<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity()]
class BlogArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(unique: true)]
    #[Assert\NotBlank]
    public ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $abstract = null;

    #[ORM\OneToMany(mappedBy: 'blogArticle', targetEntity: ContentBlock::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contents;

    #[ORM\ManyToOne(cascade: ['persist'], inversedBy: 'blogArticles')]
    private ?Category $category = null;

    public function __construct()
    {
        $this->contents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * @return Collection<int, ContentBlock>
     */
    public function getContents(): Collection
    {
        return $this->contents;
    }

    public function addContent(ContentBlock $contentBlock): self
    {
        if (!$this->contents->contains($contentBlock)) {
            $this->contents->add($contentBlock);
            $contentBlock->blogArticle = $this;
        }

        return $this;
    }

    public function removeContent(ContentBlock $contentBlock): self
    {
        $contentBlock->blogArticle = null;
        $this->contents->removeElement($contentBlock);

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
