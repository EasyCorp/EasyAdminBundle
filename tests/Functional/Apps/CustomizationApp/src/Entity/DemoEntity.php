<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DemoEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateField = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $timeField = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quantity = null;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDateField(): ?\DateTimeInterface
    {
        return $this->dateField;
    }

    public function setDateField(?\DateTimeInterface $dateField): self
    {
        $this->dateField = $dateField;

        return $this;
    }

    public function getTimeField(): ?\DateTimeInterface
    {
        return $this->timeField;
    }

    public function setTimeField(?\DateTimeInterface $timeField): self
    {
        $this->timeField = $timeField;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }
}
