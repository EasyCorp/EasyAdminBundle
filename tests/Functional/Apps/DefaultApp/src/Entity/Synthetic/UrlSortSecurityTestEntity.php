<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity used by UrlSortSecurityTest to verify that ?sort[...] cannot order
 * the index by a Doctrine-mapped property that isn't exposed via
 * configureFields(), and cannot smuggle extra ORDER BY columns through the
 * value or the key.
 */
#[ORM\Entity]
class UrlSortSecurityTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $displayedField = null;

    #[ORM\Column(length: 255)]
    private ?string $hiddenField = null;

    #[ORM\Column(length: 255)]
    private ?string $otherField = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDisplayedField(): ?string
    {
        return $this->displayedField;
    }

    public function setDisplayedField(?string $displayedField): self
    {
        $this->displayedField = $displayedField;

        return $this;
    }

    public function getHiddenField(): ?string
    {
        return $this->hiddenField;
    }

    public function setHiddenField(?string $hiddenField): self
    {
        $this->hiddenField = $hiddenField;

        return $this;
    }

    public function getOtherField(): ?string
    {
        return $this->otherField;
    }

    public function setOtherField(?string $otherField): self
    {
        $this->otherField = $otherField;

        return $this;
    }

    public function __toString(): string
    {
        return $this->displayedField ?? '';
    }
}
