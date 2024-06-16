<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity()]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Assert\NotBlank]
    public ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: BlogArticle::class)]
    private Collection $blogArticles;

    public function __construct()
    {
        $this->blogArticles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, BlogArticle>
     */
    public function getBlogArticles(): Collection
    {
        return $this->blogArticles;
    }

    public function addBlogArticle(BlogArticle $blogArticle): self
    {
        if (!$this->blogArticles->contains($blogArticle)) {
            $this->blogArticles->add($blogArticle);
            $blogArticle->setCategory($this);
        }

        return $this;
    }

    public function removeBlogArticle(BlogArticle $blogArticle): self
    {
        if ($this->blogArticles->removeElement($blogArticle)) {
            // set the owning side to null (unless already changed)
            if ($blogArticle->getCategory() === $this) {
                $blogArticle->setCategory(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
