<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\ProjectDomain;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Embedded]
    private Money $price;

    #[ORM\OneToOne]
    private ?ProjectRelease $latestRelease = null;

    #[ORM\ManyToOne]
    private ?Developer $leadDeveloper = null;

    /**
     * @var Collection<int, ProjectIssue>
     */
    #[ORM\OneToMany(targetEntity: ProjectIssue::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projectIssues;

    /**
     * @var Collection<int, Developer>
     */
    #[ORM\OneToMany(targetEntity: Developer::class, mappedBy: 'favouriteProject')]
    private Collection $favouriteProjectOf;

    /**
     * @var Collection<int, ProjectTag>
     */
    #[ORM\ManyToMany(targetEntity: ProjectTag::class, inversedBy: 'projects')]
    private Collection $projectTags;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $statesSimpleArray = [];

    #[ORM\Column(type: Types::JSON)]
    private array $rolesJson = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTime $startDate = null;

    #[ORM\Column]
    private bool $internal = false;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $startDateMutable = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startDateImmutable = null;

    #[ORM\Column]
    private ?\DateTime $startDateTimeMutable = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startDateTimeImmutable = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $startDateTimeTzMutable = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $startDateTimeTzImmutable = null;

    #[ORM\Column]
    private ?int $countInteger = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $countSmallint = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $priceDecimal = null;

    #[ORM\Column]
    private ?float $priceFloat = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $startTimeMutable = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $startTimeImmutable = null;

    public function __construct()
    {
        $this->price = (new Money())->setAmount(0)->setCurrency('EUR');
        $this->projectIssues = new ArrayCollection();
        $this->favouriteProjectOf = new ArrayCollection();
        $this->projectTags = new ArrayCollection();
    }

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

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getLatestRelease(): ?ProjectRelease
    {
        return $this->latestRelease;
    }

    public function setLatestRelease(?ProjectRelease $latestRelease): static
    {
        $this->latestRelease = $latestRelease;

        return $this;
    }

    public function getLeadDeveloper(): ?Developer
    {
        return $this->leadDeveloper;
    }

    public function setLeadDeveloper(?Developer $leadDeveloper): static
    {
        $this->leadDeveloper = $leadDeveloper;

        return $this;
    }

    /**
     * @return Collection<int, ProjectIssue>
     */
    public function getProjectIssues(): Collection
    {
        return $this->projectIssues;
    }

    public function addProjectIssue(ProjectIssue $projectIssue): static
    {
        if (!$this->projectIssues->contains($projectIssue)) {
            $this->projectIssues->add($projectIssue);
            $projectIssue->setProject($this);
        }

        return $this;
    }

    public function removeProjectIssue(ProjectIssue $projectIssue): static
    {
        if ($this->projectIssues->removeElement($projectIssue)) {
            // set the owning side to null (unless already changed)
            if ($projectIssue->getProject() === $this) {
                $projectIssue->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Developer>
     */
    public function getFavouriteProjectOf(): Collection
    {
        return $this->favouriteProjectOf;
    }

    public function addFavouriteProjectOf(Developer $favouriteProjectOf): static
    {
        if (!$this->favouriteProjectOf->contains($favouriteProjectOf)) {
            $this->favouriteProjectOf->add($favouriteProjectOf);
            $favouriteProjectOf->setFavouriteProject($this);
        }

        return $this;
    }

    public function removeFavouriteProjectOf(Developer $favouriteProjectOf): static
    {
        if ($this->favouriteProjectOf->removeElement($favouriteProjectOf)) {
            // set the owning side to null (unless already changed)
            if ($favouriteProjectOf->getFavouriteProject() === $this) {
                $favouriteProjectOf->setFavouriteProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProjectTag>
     */
    public function getProjectTags(): Collection
    {
        return $this->projectTags;
    }

    public function addProjectTag(ProjectTag $projectTag): static
    {
        if (!$this->projectTags->contains($projectTag)) {
            $this->projectTags->add($projectTag);
        }

        return $this;
    }

    public function removeProjectTag(ProjectTag $projectTag): static
    {
        $this->projectTags->removeElement($projectTag);

        return $this;
    }

    public function getRolesJson(): array
    {
        return $this->rolesJson;
    }

    public function setRolesJson(array $rolesJson): static
    {
        $this->rolesJson = $rolesJson;

        return $this;
    }

    public function getStatesSimpleArray(): array
    {
        return $this->statesSimpleArray;
    }

    public function setStatesSimpleArray(array $statesSimpleArray): static
    {
        $this->statesSimpleArray = $statesSimpleArray;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function isInternal(): ?bool
    {
        return $this->internal;
    }

    public function setInternal(bool $internal): static
    {
        $this->internal = $internal;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDateMutable(): ?\DateTime
    {
        return $this->startDateMutable;
    }

    public function setStartDateMutable(\DateTime $startDateMutable): static
    {
        $this->startDateMutable = $startDateMutable;

        return $this;
    }

    public function getStartDateImmutable(): ?\DateTimeImmutable
    {
        return $this->startDateImmutable;
    }

    public function setStartDateImmutable(\DateTimeImmutable $startDateImmutable): static
    {
        $this->startDateImmutable = $startDateImmutable;

        return $this;
    }

    public function getStartDateTimeMutable(): ?\DateTime
    {
        return $this->startDateTimeMutable;
    }

    public function setStartDateTimeMutable(\DateTime $startDateTimeMutable): static
    {
        $this->startDateTimeMutable = $startDateTimeMutable;

        return $this;
    }

    public function getStartDateTimeImmutable(): ?\DateTimeImmutable
    {
        return $this->startDateTimeImmutable;
    }

    public function setStartDateTimeImmutable(\DateTimeImmutable $startDateTimeImmutable): static
    {
        $this->startDateTimeImmutable = $startDateTimeImmutable;

        return $this;
    }

    public function getStartDateTimeTzMutable(): ?\DateTime
    {
        return $this->startDateTimeTzMutable;
    }

    public function setStartDateTimeTzMutable(\DateTime $startDateTimeTzMutable): static
    {
        $this->startDateTimeTzMutable = $startDateTimeTzMutable;

        return $this;
    }

    public function getStartDateTimeTzImmutable(): ?\DateTimeImmutable
    {
        return $this->startDateTimeTzImmutable;
    }

    public function setStartDateTimeTzImmutable(\DateTimeImmutable $startDateTimeTzImmutable): static
    {
        $this->startDateTimeTzImmutable = $startDateTimeTzImmutable;

        return $this;
    }

    public function getCountInteger(): ?int
    {
        return $this->countInteger;
    }

    public function setCountInteger(int $countInteger): static
    {
        $this->countInteger = $countInteger;

        return $this;
    }

    public function getCountSmallint(): ?int
    {
        return $this->countSmallint;
    }

    public function setCountSmallint(int $countSmallint): static
    {
        $this->countSmallint = $countSmallint;

        return $this;
    }

    public function getPriceDecimal(): ?string
    {
        return $this->priceDecimal;
    }

    public function setPriceDecimal(string $priceDecimal): static
    {
        $this->priceDecimal = $priceDecimal;

        return $this;
    }

    public function getPriceFloat(): ?float
    {
        return $this->priceFloat;
    }

    public function setPriceFloat(float $priceFloat): static
    {
        $this->priceFloat = $priceFloat;

        return $this;
    }

    public function getStartTimeMutable(): ?\DateTime
    {
        return $this->startTimeMutable;
    }

    public function setStartTimeMutable(\DateTime $startTimeMutable): static
    {
        $this->startTimeMutable = $startTimeMutable;

        return $this;
    }

    public function getStartTimeImmutable(): ?\DateTimeImmutable
    {
        return $this->startTimeImmutable;
    }

    public function setStartTimeImmutable(\DateTimeImmutable $startTimeImmutable): static
    {
        $this->startTimeImmutable = $startTimeImmutable;

        return $this;
    }
}
